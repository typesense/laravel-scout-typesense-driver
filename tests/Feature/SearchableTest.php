<?php

namespace Typesense\LaravelTypesense\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\LaravelTypesense\Tests\Fixtures\SearchableUserModel;
use Typesense\LaravelTypesense\Tests\TestCase;

class SearchableTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->setUpFaker();
        $this->loadLaravelMigrations();

        SearchableUserModel::create([
            'name' => 'Laravel Typsense',
            'email' => 'typesense@example.com',
            'password' => bcrypt('password'),
        ]);
        SearchableUserModel::create([
            'name' => 'Laravel Typsense',
            'email' => 'fake@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function testSearchByEmail()
    {
        $models = SearchableUserModel::search('typesense@example.com')->get();

        $this->assertCount(1, $models);
    }

    public function testSearchByName()
    {
        $models = SearchableUserModel::search('Laravel Typsense')->get();

        $this->assertCount(2, $models);
    }

    public function testSearchByWrongQueryParam()
    {
        $models = SearchableUserModel::search('test@example.com')->get();

        $this->assertCount(0, $models);
    }
}
