<?php

namespace Devloops\LaravelTypesense\Interfaces;

/**
 * Interface TypesenseSearch
 */
interface TypesenseSearch
{
    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;
}
