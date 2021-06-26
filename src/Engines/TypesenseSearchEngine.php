<?php

namespace Devloops\LaravelTypesense\Engines;

use Exception;
use Laravel\Scout\Builder;
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

    private Typesense $typesense;

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
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     *
     * @throws \Http\Client\Exception
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function update($models): void
    {
        $collection = $this->typesense->getCollectionIndex($models->first());

        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
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
        return $this->performSearch($builder, array_filter([
          'q'         => $builder->query,
          'query_by'  => implode(',', $builder->model->typesenseQueryBy()),
          'filter_by' => $this->filters($builder),
          'per_page'  => $builder->limit,
          'page'      => 1,
        ]));
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
        return $this->performSearch($builder, array_filter([
          'q'         => $builder->query,
          'query_by'  => implode(',', $builder->model->typesenseQueryBy()),
          'filter_by' => $this->filters($builder),
          'per_page'  => $perPage,
          'page'      => $page,
        ]));
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
     * @param  \Laravel\Scout\Builder  $builder
     *
     * @return string
     */
    protected function filters(Builder $builder): string
    {
        return collect($builder->wheres)
          ->map(static fn($value, $key) => $key.':='.$value)
          ->values()
          ->implode(' && ');
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