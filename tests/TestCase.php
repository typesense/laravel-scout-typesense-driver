<?php

namespace Typesense\LaravelTypesense\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as Orchestra;
use Typesense\LaravelTypesense\Tests\Fixtures\UserModel;
use Typesense\LaravelTypesense\TypesenseServiceProvider;

abstract class TestCase extends Orchestra
{
    use WithFaker;

    protected function getPackageProviders($app)
    {
        return [TypesenseServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app->make('config')->set('scout.driver', 'typesense');
        $app->make('config')->set('scout.typesense',
            [
                'api_key'         => 'xyz',
                'nodes'           => [
                    [
                        'host'     => 'localhost',
                        'port'     => '8108',
                        'path'     => '',
                        'protocol' => 'http',
                    ],
                ],
                'nearest_node'    => [
                    'host'     => 'localhost',
                    'port'     => '8108',
                    'path'     => '',
                    'protocol' => 'http',
                ],
                'connection_timeout_seconds'   => 2,
                'healthcheck_interval_seconds' => 30,
                'num_retries'                  => 3,
                'retry_interval_seconds'       => 1,
            ]
        );
    }

    protected function defineDatabaseMigrations()
    {
        $this->setUpFaker();
        $this->loadLaravelMigrations();

        UserModel::create([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'asd'
        ]);

        UserModel::create([
            'name' => 'Abigail Otwell',
            'email' => 'abigail@laravel.com',
            'password' => 'asd'
        ]);
    }
}
