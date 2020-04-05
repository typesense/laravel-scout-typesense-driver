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
                  'master_node'        => config(
                    'scout.typesensesearch.master_node',
                    []
                  ),
                  'read_replica_nodes' => !config(
                    'scout.typesensesearch.enabled_read_replica',
                    false
                  ) ? [] : config('scout.typesensesearch.read_replicas', []),
                  'timeout_seconds'    => config(
                    'scout.typesensesearch.timeout',
                    2.0
                  ),
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
                  'master_node'        => config(
                    'scout.typesensesearch.master_node',
                    []
                  ),
                  'read_replica_nodes' => !config(
                    'scout.typesensesearch.enabled_read_replica',
                    false
                  ) ? [] : config('scout.typesensesearch.read_replicas', []),
                  'timeout_seconds'    => config(
                    'scout.typesensesearch.timeout',
                    2.0
                  ),
                ]
              );

              return new Typesense($client);
          }
        );

        $this->app->alias(Typesense::class, 'typesense');
    }

}