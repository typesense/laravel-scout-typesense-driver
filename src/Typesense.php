<?php

namespace Devloops\LaravelTypesense;

use Devloops\LaravelTypesense\Classes\TypesenseDocumentIndexResponse;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Document;
use Typesense\Exceptions\ObjectNotFound;

/**
 * Class Typesense.
 *
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
     * @param $model
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
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
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @return \Typesense\Collection
     */
    public function getCollectionIndex($model): Collection
    {
        return $this->getOrCreateCollectionFromModel($model);
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param $array
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @return \Devloops\LaravelTypesense\Classes\TypesenseDocumentIndexResponse
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
     * @param \Typesense\Collection $collectionIndex
     * @param $modelId
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @return array
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
     * @param \Typesense\Collection $collectionIndex
     * @param array                 $query
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
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
     * @param $documents
     * @param string $action
     *
     * @throws \Http\Client\Exception
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @return \Illuminate\Support\Collection
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
     * @param string $collectionName
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\ObjectNotFound
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @return array
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
