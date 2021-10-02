<?php

namespace Devloops\LaravelTypesense;

use Typesense\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\Facades\Config;
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
            $client = new Client(Config::get('scout.typesense'));
            return new TypesenseSearchEngine(new Typesense($client));
        });

        $this->registerMacros();
    }

    public function register(): void
    {
        $this->app->singleton(Typesense::class, static function () {
            $client = new Client(Config::get('scout.typesense'));
            return new Typesense($client);
        });

        $this->app->alias(Typesense::class, 'typesense');
    }

    private function registerMacros(): void
    {
        Builder::macro('count', function () {
            return $this->engine()
                        ->getTotalCount($this->engine()
                                             ->search($this));
        });

        Builder::macro('orderByLocation', function (string $column, float $lat, float $lng, string $direction = 'asc') {
            $this->engine()
                 ->orderByLocation($column, $lat, $lng, $direction);
            return $this;
        });

        Builder::macro('groupBy', function (array|string $groupBy) {
            $groupBy = is_array($groupBy) ? $groupBy : func_get_args();
            $this->engine()
                 ->groupBy($groupBy);
            return $this;
        });

        Builder::macro('groupByLimit', function (int $groupByLimit) {
            $this->engine()
                 ->groupByLimit($groupByLimit);
            return $this;
        });

        Builder::macro('setHighlightStartTag', function (string $startTag) {
            $this->engine()
                 ->setHighlightStartTag($startTag);
            return $this;
        });

        Builder::macro('setHighlightEndTag', function (string $endTag) {
            $this->engine()
                 ->setHighlightEndTag($endTag);
            return $this;
        });

        Builder::macro('limitHits', function (int $limitHits) {
            $this->engine()
                 ->limitHits($limitHits);
            return $this;
        });
    }

}
