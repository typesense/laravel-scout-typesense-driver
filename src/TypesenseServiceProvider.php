<?php

namespace Devloops\LaravelTypesense;

use Typesense\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Devloops\LaravelTypesense\Engines\TypesenseSearchEngine;

/**
 * Class TypesenseServiceProvider
 *
 * @package Devloops\LaravelTypesense
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->app[EngineManager::class]->extend('typesense', static function ($app) {
            $client = new Client(config('scout.typesense'));
            return new TypesenseSearchEngine(new Typesense($client));
        });

        Builder::macro('count', function () {
            return $this->engine()->getTotalCount($this->engine()->search($this));
        });
    }

    public function register(): void
    {
        $this->app->singleton(Typesense::class, static function () {
            $client = new Client(config('scout.typesense'));
            return new Typesense($client);
        });

        $this->app->alias(Typesense::class, 'typesense');
    }

}
