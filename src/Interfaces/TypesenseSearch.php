<?php

namespace Typesense\LaravelTypesense\Interfaces;

/**
 * Interface TypesenseSearch
 *
 * @package Typesense\LaravelTypesense\Interfaces
 */
interface TypesenseSearch
{

    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;

}