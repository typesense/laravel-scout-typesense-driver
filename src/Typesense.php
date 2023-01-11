<?php

namespace Typesense\LaravelTypesense;

use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;
use Typesense\Exceptions\TypesenseClientError;
use Typesense\LaravelTypesense\Classes\TypesenseDocumentIndexResponse;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Document;
use Laravel\Scout\Builder;
use Typesense\LaravelTypesense\Mixin\BuilderMixin;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\LaravelTypesense\Engines\TypesenseEngine;

/**
 * Class Typesense
 *
 * @package Typesense\LaravelTypesense
 * @date    4/5/20
 *
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class Typesense
{
    /**
     * @var \Typesense\Client
     */
    private Client $client;

    /**
     * Typesense constructor.
     *
     * @param \Typesense\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return \Typesense\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param $key
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     * @throws \Typesense\Exceptions\ConfigError
     */
    public function setScopedApiKey($key): void
    {
        $config = Config::get('scout.typesense');
        $config['api_key'] = $key;
        $client = new Client($config);

        app()[EngineManager::class]->extend('typesense', static function () use ($client) {
            return new TypesenseEngine(new Typesense($client));
        });
        Builder::mixin(app()->make(BuilderMixin::class));
    }

    /**
     * @param $model
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return \Typesense\Collection
     */
    private function getOrCreateCollectionFromModel($model): Collection
    {
        $index = $this->client->getCollections()->{$model->searchableAs()};

        try {
            $index->retrieve();

            return $index;
        } catch (ObjectNotFound $exception) {
            $this->client->getCollections()
                         ->create($model->getCollectionSchema());

            return $this->client->getCollections()->{$model->searchableAs()};
        }
    }

    /**
     * @param $model
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return \Typesense\Collection
     */
    public function getCollectionIndex($model): Collection
    {
        return $this->getOrCreateCollectionFromModel($model);
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param                       $array
     *
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return \Typesense\LaravelTypesense\Classes\TypesenseDocumentIndexResponse
     */
    public function upsertDocument(Collection $collectionIndex, $array): TypesenseDocumentIndexResponse
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[$array['id']];

        try {
            $document->retrieve();
            $document->delete();

            return new TypesenseDocumentIndexResponse(200, true, null, $collectionIndex->getDocuments()
                                                                                       ->create($array));
        } catch (ObjectNotFound) {
            return new TypesenseDocumentIndexResponse(200, true, null, $collectionIndex->getDocuments()
                                                                                       ->create($array));
        }
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param                       $modelId
     *
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return array
     */
    public function deleteDocument(Collection $collectionIndex, $modelId): array
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[(string) $modelId];

        return $document->delete();
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param array                 $query
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return array
     */
    public function deleteDocuments(Collection $collectionIndex, array $query): array
    {
        return $collectionIndex->getDocuments()
                               ->delete($query);
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param                       $documents
     * @param string                $action
     *
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return \Illuminate\Support\Collection
     */
    public function importDocuments(Collection $collectionIndex, $documents, string $action = 'upsert'): \Illuminate\Support\Collection
    {
        $importedDocuments = $collectionIndex->getDocuments()
                                             ->import($documents, ['action' => $action]);

        $result = [];
        foreach ($importedDocuments as $importedDocument) {
            if (!$importedDocument['success']) {
                throw new TypesenseClientError("Error importing document: ${importedDocument['error']}");
            }

            $result[] = new TypesenseDocumentIndexResponse($importedDocument['code'] ?? 0, $importedDocument['success'], $importedDocument['error'] ?? null, json_decode($importedDocument['document'] ?? '[]', true, 512, JSON_THROW_ON_ERROR));
        }

        return collect($result);
    }

    /**
     * @param string $collectionName
     *
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     *
     * @return array
     */
    public function deleteCollection(string $collectionName): array
    {
        return $this->client->getCollections()->{$collectionName}->delete();
    }
}
