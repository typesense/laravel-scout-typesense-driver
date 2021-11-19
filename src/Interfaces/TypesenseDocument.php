<?php

namespace Devloops\LaravelTypesense\Interfaces;

/**
 * Interface TypesenseSearch
 *
 * @package Devloops\LaravelTypesense\Interfaces
 */
interface TypesenseDocument
{
    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;
}
