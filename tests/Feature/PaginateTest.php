<?php

namespace Typesense\LaravelTypesense\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Typesense\LaravelTypesense\Tests\Fixtures\SearchableUserModel;
use Typesense\LaravelTypesense\Tests\TestCase;

class PaginateTest extends TestCase
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
        SearchableUserModel::create([
            'name' => 'Laravel Typsense',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function testPaginate()
    {
        $response = SearchableUserModel::search('Laravel Typsense')->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $response);
        $this->assertInstanceOf(SearchableUserModel::class, $response->items()[0]);
        $this->assertEquals(3, $response->total());
        $this->assertEquals(1, $response->lastPage());
    }
}
