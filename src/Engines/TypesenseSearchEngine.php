<?php

namespace Devloops\LaravelTypesense\Engines;

use Exception;
use Laravel\Scout\Builder;
use Illuminate\Support\Str;
use Laravel\Scout\Engines\Engine;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Model;
use Devloops\LaravelTypesense\Typesense;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TypesenseSearchEngine
 *
 * @package Devloops\LaravelTypesense\Engines
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseSearchEngine extends Engine
{

    /**
     * @var \Devloops\LaravelTypesense\Typesense
     */
    private Typesense $typesense;

    /**
     * @var array
     */
    private array $groupBy = [];

    /**
     * @var int
     */
    private int $groupByLimit = 3;

    /**
     * @var string
     */
    private string $startTag = '<mark>';

    /**
     * @var string
     */
    private string $endTag = '</mark>';

    /**
     * @var int
     */
    private int $limitHits = -1;

    /**
     * @var array
     */
    private array $locationOrderBy = [];

    /**
     * TypesenseSearchEngine constructor.
     *
     * @param  \Devloops\LaravelTypesense\Typesense  $typesense
     */
    public function __construct(Typesense $typesense)
    {
        $this->typesense = $typesense;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>|Model[]  $models
     *
     * @throws \Http\Client\Exception
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @noinspection NotOptimalIfConditionsInspection
     */
    public function update($models): void
    {
        $collection = $this->typesense->getCollectionIndex($models->first());

        if ($this->usesSoftDelete($models->first()) && $models->first()->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $this->typesense->importDocuments($collection, $models->map(fn($m) => $m->toSearchableArray())
                                                              ->toArray());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function delete($models): void
    {
        $models->each(function (Model $model) {
            $collectionIndex = $this->typesense->getCollectionIndex($model);

            $this->typesense->deleteDocument($collectionIndex, $model->{$model->getKey()});
        });
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     *
     * @return mixed
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function search(Builder $builder): mixed
    {
        return $this->performSearch($builder, array_filter($this->buildSearchParams($builder, 1, $builder->limit)));
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     *
     * @return mixed
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function paginate(Builder $builder, $perPage, $page): mixed
    {
        return $this->performSearch($builder, array_filter($this->buildSearchParams($builder, $page, $perPage)));
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $page
     * @param  int  $perPage
     *
     * @return array
     */
    private function buildSearchParams(Builder $builder, int $page, int $perPage): array
    {
        $params = [
          'q'                   => $builder->query,
          'query_by'            => implode(',', $builder->model->typesenseQueryBy()),
          'filter_by'           => $this->filters($builder),
          'per_page'            => $perPage,
          'page'                => $page,
          'highlight_start_tag' => $this->startTag,
          'highlight_end_tag'   => $this->endTag,
        ];

        if ($this->limitHits > 0) {
            $params['limit_hits'] = $this->limitHits;
        }

        if (!empty($this->groupBy)) {
            $params['group_by']    = implode(',', $this->groupBy);
            $params['group_limit'] = $this->groupByLimit;
        }

        if (!empty($this->locationOrderBy)) {
            $params['sort_by'] = $this->parseOrderByLocation(...$this->locationOrderBy);
        }

        if ($builder->orders) {
            $params['sort_by'] .= $this->parseOrderBy($builder->orders);
        }

        return $params;
    }

    /**
     * Parse location order by for sort_by
     *
     * @param  string  $column
     * @param  float  $lat
     * @param  float  $lng
     * @param  string  $radius
     * @param  string  $direction
     * @param  bool  $exclude_radius
     *
     * @return string
     * @noinspection PhpPureAttributeCanBeAddedInspection
     */
    private function parseOrderByLocation(string $column, float $lat, float $lng, string $radius, string $direction = 'asc', bool $exclude_radius = false): string
    {
        $direction = Str::lower($direction) === 'asc' ? 'asc' : 'desc';
        $str       = $column.'('.$lat.', '.$lng.', ';
        if ($exclude_radius) {
            $str .= 'exclude_radius: '.$radius;
        } else {
            $str .= $radius;
        }
        return $str.'):'.$direction;
    }

    /**
     * Parse sort_by fields
     *
     * @param  array  $orders
     *
     * @return string
     */
    private function parseOrderBy(array $orders): string
    {
        $sortByArr = [];
        foreach ($orders as $order) {
            $sortByArr[] = $order['column'].':'.$order['direction'];
        }
        return implode(',', $sortByArr);
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     *
     * @return mixed
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    protected function performSearch(Builder $builder, array $options = []): mixed
    {
        $documents = $this->typesense->getCollectionIndex($builder->model)
                                     ->getDocuments();
        if ($builder->callback) {
            return call_user_func($builder->callback, $documents, $builder->query, $options);
        }
        return $documents->search($options);
    }

    /**
     * Prepare filters
     *
     * @param  Builder  $builder
     *
     * @return string
     */
    protected function filters(Builder $builder): string
    {
        return collect($builder->wheres)
          ->map([
            $this,
            'parseFilters',
          ])
          ->values()
          ->implode(' && ');
    }

    /**
     * Parse typesense filters
     *
     * @param  array|string  $value
     * @param  string  $key
     *
     * @return string
     */
    public function parseFilters(array|string $value, string $key): string
    {
        if (is_array($value)) {
            return sprintf('%s:%s', $key, implode('', $value));
        }
        return sprintf('%s:=%s', $key, $value);
    }

    /**
     * @param  mixed  $results
     *
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results): Collection
    {
        return collect($results['hits'])
          ->pluck('document.id')
          ->values();
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model): \Illuminate\Database\Eloquent\Collection
    {
        if ((int) ($results['found'] ?? 0) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['hits'])
          ->pluck('document.id')
          ->values()
          ->all();

        $objectIdPositions = array_flip($objectIds);
        return $model->getScoutModelsByIds($builder, $objectIds)
                     ->filter(static function ($model) use ($objectIds) {
                         return in_array($model->getScoutKey(), $objectIds, false);
                     })
                     ->sortBy(static function ($model) use ($objectIdPositions) {
                         return $objectIdPositions[$model->getScoutKey()];
                     })
                     ->values();
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount($results): int
    {
        return (int) ($results['found'] ?? 0);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function flush($model): void
    {
        $collection = $this->typesense->getCollectionIndex($model);
        $collection->delete();
    }

    /**
     * @param $model
     *
     * @return bool
     */
    protected function usesSoftDelete($model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        if ((int) ($results['found'] ?? 0) === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = collect($results['hits'])
          ->pluck('document.id')
          ->values()
          ->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds($builder, $objectIds)
                     ->cursor()
                     ->filter(static function ($model) use ($objectIds) {
                         return in_array($model->getScoutKey(), $objectIds, false);
                     })
                     ->sortBy(static function ($model) use ($objectIdPositions) {
                         return $objectIdPositions[$model->getScoutKey()];
                     })
                     ->values();
    }

    /**
     * @param  string  $name
     * @param  array  $options
     *
     * @return void
     * @throws \Exception
     */
    public function createIndex($name, array $options = []): void
    {
        throw new Exception('Typesense indexes are created automatically upon adding objects.');
    }

    /**
     * You can aggregate search results into groups or buckets by specify one or more group_by fields. Separate multiple fields with a comma.
     *
     * @param  mixed  $groupBy
     *
     * @return $this
     */
    public function groupBy(array $groupBy): static
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Maximum number of hits to be returned for every group. (default: 3)
     *
     * @param  int  $groupByLimit
     *
     * @return $this
     */
    public function groupByLimit(int $groupByLimit): static
    {
        $this->groupByLimit = $groupByLimit;
        return $this;
    }

    /**
     * The start tag used for the highlighted snippets. (default: <mark>)
     *
     * @param  string  $startTag
     *
     * @return $this
     */
    public function setHighlightStartTag(string $startTag): static
    {
        $this->startTag = $startTag;
        return $this;
    }

    /**
     * The end tag used for the highlighted snippets. (default: </mark>)
     *
     * @param  string  $endTag
     *
     * @return $this
     */
    public function setHighlightEndTag(string $endTag): static
    {
        $this->endTag = $endTag;
        return $this;
    }

    /**
     * Maximum number of hits that can be fetched from the collection (default: no limit)
     *
     * (page * per_page) should be less than this number for the search request to return results.
     *
     * @param  int  $limitHits
     *
     * @return $this
     */
    public function limitHits(int $limitHits): static
    {
        $this->limitHits = $limitHits;
        return $this;
    }

    public function orderByLocation(string $column, float $lat, float $lng, string $radius, bool $excludeRadius): static
    {
        $this->locationOrderBy = [
          'column'         => $excludeRadius,
          'lat'            => $lat,
          'lng'            => $lng,
          'radius'         => $radius,
          'exclude_radius' => $excludeRadius,
        ];
        return $this;
    }

    /**
     * @param  string  $name
     *
     * @return array
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function deleteIndex($name): array
    {
        return $this->typesense->deleteCollection($name);
    }

}
