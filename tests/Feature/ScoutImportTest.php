<?php

namespace Typesense\LaravelTypesense\Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Console\ImportCommand;
use Typesense\LaravelTypesense\Tests\Fixtures\SearchableUserModel;
use Typesense\LaravelTypesense\Tests\TestCase;

class ScoutImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Testbench does not load Scout's console provider here.
        $this->app[Kernel::class]->registerCommand(
            $this->app->make(ImportCommand::class)
        );
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        parent::defineDatabaseMigrationsAfterDatabaseRefreshed();

        SearchableUserModel::withoutSyncingToSearch(function () {
            SearchableUserModel::create([
                'name' => 'Laravel Typsense Import',
                'email' => 'typesense-import@example.com',
                'password' => bcrypt('password'),
            ]);

            SearchableUserModel::create([
                'name' => 'Laravel Typsense Import',
                'email' => 'fake-import@example.com',
                'password' => bcrypt('password'),
            ]);
        });
    }

    public function testScoutImportIndexesExistingModels()
    {
        $this->artisan('scout:import', [
            'model' => SearchableUserModel::class,
            '--fresh' => true,
        ])->assertExitCode(0);

        $models = SearchableUserModel::search('Laravel Typsense Import')->get();

        $this->assertCount(2, $models);
        $this->assertEqualsCanonicalizing(
            [
                'fake-import@example.com',
                'typesense-import@example.com',
            ],
            $models->pluck('email')->all()
        );
    }
}
