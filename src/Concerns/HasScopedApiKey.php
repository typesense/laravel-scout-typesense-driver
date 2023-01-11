<?php

namespace Typesense\LaravelTypesense\Concerns;

use Typesense\LaravelTypesense\Typesense;

trait HasScopedApiKey
{
    /**
     * @param $key
     * @return static
     */
    public static function setScopedApiKey($key): static
    {
        app(Typesense::class)->setScopedApiKey($key);

        return new static;
    }
}
