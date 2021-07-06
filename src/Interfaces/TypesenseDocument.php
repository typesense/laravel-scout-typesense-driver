<?php

namespace Typesense\LaravelTypesense\Interfaces;

/**
 * Interface TypesenseSearch
 *
 * @package Typesense\LaravelTypesense\Interfaces
 */
interface TypesenseDocument
{

    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;

}