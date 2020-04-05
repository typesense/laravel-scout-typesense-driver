<?php

namespace Devloops\LaravelTypesense\Engines;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Devloops\LaravelTypesense\Typesense;
use GuzzleHttp\Exception\GuzzleException;
use Devloops\Typesence\Exceptions\TypesenseClientError;

/**
 * Class TypesenseSearchEngine
 *
 * @package Devloops\LaravelTypesense\Engines
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseSearchEngine extends Engine
{

    private $typesense;

    /**
     * TypesenseSearchEngine constructor.
     *
     * @param $typesense
     */
    public function __construct(Typesense $typesense)
    {
        $this->typesense = $typesense;
    }

    /**
     * @inheritDoc
     */
    public function update($models): void
    {
        $models->each(
          function (Model $model) {
              $array = $model->toSearchableArray();

              $collectionIndex = $this->typesense->getCollectionIndex($model);

              $this->typesense->upsertDocument($collectionIndex, $array);
          }
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($models): void
    {
        $models->each(
          function (Model $model) {
              $collectionIndex = $this->typesense->getCollectionIndex($model);

              $this->typesense->deleteDocument(
                $collectionIndex,
                $model->{$model->getKey()}
              );
          }
        );
    }

    /**
     * @inheritDoc
     */
    public function search(Builder $builder)
    {
        return $this->performSearch(
          $builder,
          array_filter(
            [
              'q'        => $builder->query,
              'query_by' => implode(',', $builder->model->typesenseQueryBy()),
              'fiter_by' => $this->filters($builder),
              'per_page' => $builder->limit,
              'page'     => 1,
            ]
          )
        );
    }

    /**
     * @inheritDoc
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch(
          $builder,
          [
            'q'        => $builder->query,
            'query_by' => implode(',', $builder->model->typesenseQueryBy()),
            'fiter_by' => $this->filters($builder),
            'per_page' => $builder->limit,
            'page'     => 1,
          ]
        );
    }

    /**
     * @param   \Laravel\Scout\Builder  $builder
     * @param   array                   $options
     *
     * @return array|mixed
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $documents =
          $this->typesense->getCollectionIndex($builder->model)->getDocuments();
        if ($builder->callback) {
            return call_user_func(
              $builder->callback,
              $documents,
              $builder->query,
              $options
            );
        }
        return $documents->search(
          $options
        );
    }

    /**
     * @param   \Laravel\Scout\Builder  $builder
     *
     * @return array
     */
    protected function filters(Builder $builder): array
    {
        return collect($builder->wheres)->map(
          static function ($value, $key) {
              return $key . ':=' . $value;
          }
        )->values()->all();
    }

    /**
     * @inheritDoc
     */
    public function mapIds($results): Collection
    {
        return collect($results['hits'])->pluck('document.id')->values();
    }

    /**
     * @inheritDoc
     */
    public function map(Builder $builder, $results, $model)
    {
        if ((int)($results['found'] ?? 0) === 0) {
            return $model->newCollection();
        }

        $objectIds         =
          collect($results['hits'])->pluck('document.id')->values()->all();
        $objectIdPositions = array_flip($objectIds);
        return $model->getScoutModelsByIds(
          $builder,
          $objectIds
        )->filter(
          static function ($model) use ($objectIds) {
              return in_array($model->getScoutKey(), $objectIds, false);
          }
        )->sortBy(
          static function ($model) use ($objectIdPositions) {
              return $objectIdPositions[$model->getScoutKey()];
          }
        )->values();
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount($results): int
    {
        return (int)($results['found'] ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function flush($model): void
    {
        $collection = $this->typesense->getCollectionIndex($model);
        try {
            $collection->delete();
        } catch (TypesenseClientError $e) {
        } catch (GuzzleException $e) {
        }
    }

}