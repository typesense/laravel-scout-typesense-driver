<?php


namespace Devloops\LaravelTypesense;


use Devloops\Typesence\Client;
use Devloops\Typesence\Document;
use Devloops\Typesence\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Devloops\Typesence\Exceptions\ObjectNotFound;
use Devloops\Typesence\Exceptions\TypesenseClientError;

/**
 * Class Typesense
 *
 * @package Devloops\LaravelTypesense
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class Typesense
{

    /**
     * @var \Devloops\Typesence\Client
     */
    private $client;

    /**
     * Typesense constructor.
     *
     * @param   \Devloops\Typesence\Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return \Devloops\Typesence\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param   \Illuminate\Database\Eloquent\Model|\Devloops\LaravelTypesense\Interfaces\TypesenseSearch  $model
     *
     * @return \Devloops\Typesence\Collection
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createCollectionFromModel($model): Collection
    {
        $index = $this->client->getCollections()->{$model->getTable()};
        try {
            $index->retrieve();

            return $index;
        } catch (ObjectNotFound $exception) {
            $this->client->getCollections()->create(
              $model->getCollectionSchema()
            );

            return $this->client->getCollections()->{$model->getTable()};
        } catch (TypesenseClientError $exception) {
            throw $exception;
        }
    }

    /**
     * @param   \Illuminate\Database\Eloquent\Model  $model
     *
     * @return \Devloops\Typesence\Collection
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCollectionIndex($model): Collection
    {
        return $this->createCollectionFromModel($model);
    }

    /**
     * @param   \Devloops\Typesence\Collection  $collectionIndex
     * @param                                   $array
     *
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upsertDocument(Collection $collectionIndex, $array): void
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[$array['id']];

        try {
            $document->retrieve();
            $document->delete();
            $collectionIndex->getDocuments()->create($array);
        } catch (ObjectNotFound $e) {
            $collectionIndex->getDocuments()->create($array);
        } catch (TypesenseClientError $e) {
        } catch (GuzzleException $e) {
        }
    }

    /**
     * @param   \Devloops\Typesence\Collection  $collectionIndex
     * @param                                   $modelId
     */
    public function deleteDocument(Collection $collectionIndex, $modelId): void
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[(string)$modelId];
        try {
            $document->delete();
        } catch (TypesenseClientError $e) {
        } catch (GuzzleException $e) {
        }
    }

}