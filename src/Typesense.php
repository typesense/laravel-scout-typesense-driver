<?php

namespace Devloops\LaravelTypesense;

use Typesense\Client;
use Typesense\Document;
use Typesense\Collection;
use Typesense\Exceptions\ObjectNotFound;
use Devloops\LaravelTypesense\Classes\TypesenseDocumentIndexResponse;

/**
 * Class Typesense
 *
 * @package Typesense\LaravelTypesense
 * @date    4/5/20
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
     * @param  \Typesense\Client  $client
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
     * @param $model
     *
     * @return \Typesense\Collection
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
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
     * @return \Typesense\Collection
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function getCollectionIndex($model): Collection
    {
        return $this->getOrCreateCollectionFromModel($model);
    }

    /**
     * @param  \Typesense\Collection  $collectionIndex
     * @param $array
     *
     * @return \Devloops\LaravelTypesense\Classes\TypesenseDocumentIndexResponse
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function upsertDocument(Collection $collectionIndex, $array): TypesenseDocumentIndexResponse
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[$array['id']] ?? null;

        if ($document === null) {
            throw new ObjectNotFound();
        }

        try {
            $document->retrieve();
            $document->delete();
            return new TypesenseDocumentIndexResponse(true, null, $collectionIndex->getDocuments()
                                                                                  ->create($array));
        } catch (ObjectNotFound) {
            return new TypesenseDocumentIndexResponse(true, null, $collectionIndex->getDocuments()
                                                                                  ->create($array));
        }
    }

    /**
     * @param  \Typesense\Collection  $collectionIndex
     * @param $modelId
     *
     * @return array
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function deleteDocument(Collection $collectionIndex, $modelId): array
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[(string) $modelId] ?? null;
        if ($document === null) {
            throw new ObjectNotFound();
        }
        return $document->delete();
    }

    /**
     * @param  \Typesense\Collection  $collectionIndex
     * @param  array  $query
     *
     * @return array
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function deleteDocuments(Collection $collectionIndex, array $query): array
    {
        return $collectionIndex->getDocuments()
                               ->delete($query);
    }

    /**
     * @param  \Typesense\Collection  $collectionIndex
     * @param $documents
     * @param  string  $action
     *
     * @return \Illuminate\Support\Collection
     * @throws \Http\Client\Exception
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function importDocuments(Collection $collectionIndex, $documents, string $action = 'upsert'): \Illuminate\Support\Collection
    {
        $importedDocuments = $collectionIndex->getDocuments()
                                             ->import($documents, ['action' => $action]);

        $result = [];
        foreach ($importedDocuments as $importedDocument) {
            $result[] = new TypesenseDocumentIndexResponse(...$importedDocument);
        }
        return collect($result);
    }

    /**
     * @param  string  $collectionName
     *
     * @return array
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     */
    public function deleteCollection(string $collectionName): array
    {
        $index = $this->client->getCollections()->{$collectionName} ?? null;
        if ($index === null) {
            throw new ObjectNotFound();
        }
        return $index->delete();
    }

}