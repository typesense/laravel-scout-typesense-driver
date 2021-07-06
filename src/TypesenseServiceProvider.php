<?php

namespace Typesense\LaravelTypesense;

use Typesense\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Typesense\LaravelTypesense\Engines\TypesenseEngine;

/**
 * Class TypesenseServiceProvider
 *
 * @package Typesense\LaravelTypesense
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->app[EngineManager::class]->extend('typesense', static function ($app) {
            $client = new Client([
              'master_node'        => config('scout.typesense.master_node', []),
              'read_replica_nodes' => !config('scout.typesense.enabled_read_replica', false) ? [] : config('scout.typesense.read_replicas', []),
              'timeout_seconds'    => config('scout.typesense.timeout', 2.0),
            ]);
            return new TypesenseEngine(new Typesense($client));
        });

        Builder::macro('count', function () {
            return $this->engine()
                        ->getTotalCount($this->engine()
                                             ->search($this));
        });
    }

    public function register(): void
    {
        $this->app->singleton(Typesense::class, static function () {
            $client = new Client([
              'master_node'        => config('scout.typesense.master_node', []),
              'read_replica_nodes' => !config('scout.typesense.enabled_read_replica', false) ? [] : config('scout.typesense.read_replicas', []),
              'timeout_seconds'    => config('scout.typesense.timeout', 2.0),
            ]);

            return new Typesense($client);
        });

        $this->app->alias(Typesense::class, 'typesense');
    }

}