<?php

namespace Typesense\LaravelTypesense;

use Typesense\LaravelTypesense\Engines\TypesenseEngine;
use Typesense\LaravelTypesense\Mixin\BuilderMixin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Typesense\Client;

/**
 * Class TypesenseServiceProvider.
 *
 * @date    4/5/20
 *
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseServiceProvider extends ServiceProvider
{
    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot(): void
    {
        $this->app[EngineManager::class]->extend('typesense', static function ($app) {
            $client = new Client(Config::get('scout.typesense'));

            return new TypesenseEngine(new Typesense($client));
        });

        $this->registerMacros();
    }

    /**
     * Register singletons and aliases.
     */
    public function register(): void
    {
        $this->app->singleton(Typesense::class, static function () {
            $client = new Client(Config::get('scout.typesense'));

            return new Typesense($client);
        });

        $this->app->alias(Typesense::class, 'typesense');
    }

    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function registerMacros(): void
    {
        Builder::mixin($this->app->make(BuilderMixin::class));
    }
}
