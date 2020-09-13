<?php

namespace Devloops\LaravelTypesense;

use Devloops\Typesence\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use Devloops\LaravelTypesense\Engines\TypesenseSearchEngine;
use Laravel\Scout\Builder;

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
        $this->app[EngineManager::class]->extend(
          'typesensesearch',
          static function ($app) {
              $client = new Client(
                [
                  'api_key'                       => config('scout.typesensesearch.api_key', ''),
                  'nodes'                         => config('scout.typesensesearch.nodes', []),
                  'nearest_node'                  => config('scout.typesensesearch.nearest_node', []),
                  'connection_timeout_seconds'    => config('scout.typesensesearch.connection_timeout_seconds', 2.0),
                  'healthcheck_interval_seconds'  => config('scout.typesensesearch.healthcheck_interval_seconds', 60),
                  'num_retries'                   => config('scout.typesensesearch.num_retries', 3),
                  'retry_interval_seconds'        => config('scout.typesensesearch.retry_interval_seconds', 1.0),
                ]
              );
              return new TypesenseSearchEngine(new Typesense($client));
          }
        );

        Builder::macro(
          'count',
          function () {
              return $this->engine()->getTotalCount(
                $this->engine()->search($this)
              );
          }
        );
    }

    public function register(): void
    {
        $this->app->singleton(
          Typesense::class,
          static function () {
              $client = new Client(
                [
                  'api_key'                       => config('scout.typesensesearch.api_key', ''),
                  'nodes'                         => config('scout.typesensesearch.nodes', []),
                  'nearest_node'                  => config('scout.typesensesearch.nearest_node', []),
                  'connection_timeout_seconds'    => config('scout.typesensesearch.connection_timeout_seconds', 2.0),
                  'healthcheck_interval_seconds'  => config('scout.typesensesearch.healthcheck_interval_seconds', 60),
                  'num_retries'                   => config('scout.typesensesearch.num_retries', 3),
                  'retry_interval_seconds'        => config('scout.typesensesearch.retry_interval_seconds', 1.0),
                ]
              );

              return new Typesense($client);
          }
        );

        $this->app->alias(Typesense::class, 'typesense');
    }

}
