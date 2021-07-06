<?php

namespace Typesense\LaravelTypesense;

use Illuminate\Contracts\Foundation\Application;
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
    public function register(): void
    {
        $this->app->singleton(
            Client::class,
            function () {
                return new Client(
                    [
                        'api_key'                       => config('scout.typesense.api_key', ''),
                        'nodes'                         => config('scout.typesense.nodes', []),
                        'nearest_node'                  => config('scout.typesense.nearest_node', []),
                        'connection_timeout_seconds'    => config('scout.typesense.connection_timeout_seconds', 2.0),
                        'healthcheck_interval_seconds'  => config('scout.typesense.healthcheck_interval_seconds', 60),
                        'num_retries'                   => config('scout.typesense.num_retries', 3),
                        'retry_interval_seconds'        => config('scout.typesense.retry_interval_seconds', 1.0),
                    ]
                );
            }
        );

        $this->app->singleton(
            Typesense::class,
            function (Client $client) {
                return new Typesense($client);
            }
        );

        $this->app->alias(Typesense::class, 'typesense');
    }

    public function boot(): void
    {
        $this->app[EngineManager::class]->extend('typesense', static function (Application $app) {
            return new TypesenseEngine(new Typesense($app->make(Client::class)));
        });

        // TODO test this manually to make sure it still works
        Builder::macro('count', function () {
            return $this->engine()
                ->getTotalCount(
                    $this->engine()->search($this)
                );
        });
    }
}
