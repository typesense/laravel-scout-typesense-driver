<?php

namespace Typesense\LaravelTypesense\Tests;

use Laravel\Scout\EngineManager;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as Orchestra;
use Typesense\LaravelTypesense\TypesenseServiceProvider;

abstract class TestCase extends Orchestra
{
    use WithFaker;

    protected function getPackageProviders($app)
    {
        $app->singleton(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });

        return [TypesenseServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $this->mergeConfigFrom($app, __DIR__.'/../config/scout.php', 'scout');
    }

    private function mergeConfigFrom($app, $path, $key)
    {
        $config = $app['config']->get($key, []);

        $app['config']->set($key, array_merge(require $path, $config));
    }
}
