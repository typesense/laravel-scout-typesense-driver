<?php

namespace Typesense\LaravelTypesense\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\LaravelTypesense\Tests\Fixtures\SearchableUserModel;
use Typesense\LaravelTypesense\Tests\TestCase;

class MultiSearchTest extends TestCase
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

    public function testSearchByEmail()
    {
        $searchRequests = [
            [
              'collection' => 'users',
              'q' => 'Laravel Typsense'
            ],
            [
              'collection' => 'users',
              'q' => 'typesense@example.com'
            ]
        ];

        $response = SearchableUserModel::search('')->searchMulti($searchRequests)->paginateRaw();

        $this->assertCount(2, $response->items()['results']);
        $this->assertEquals(3, $response->items()['results'][0]['found']);
        $this->assertEquals("test@example.com", $response->items()['results'][0]['hits'][0]['document']['email']);

    }

    public function testSearchByName()
    {
        $searchRequests = [
            [
              'collection' => 'users',
              'q' => 'Laravel Typsense'
            ],
            [
              'collection' => 'users',
              'q' => 'typesense@example.com'
            ]
        ];

        $response = SearchableUserModel::search('')->searchMulti($searchRequests)->paginateRaw();

        $this->assertCount(2, $response->items()['results']);
        $this->assertEquals(1, $response->items()['results'][1]['found']);
        $this->assertEquals("typesense@example.com", $response->items()['results'][1]['hits'][0]['document']['email']);
    }

    public function testSearchByWrongQueryParams()
    {
        $searchRequests = [
            [
              'collection' => 'users',
              'q' => 'Wrong Params'
            ],
            [
              'collection' => 'users',
              'q' => 'wrong@example.com'
            ]
        ];

        $response = SearchableUserModel::search('')->searchMulti($searchRequests)->paginateRaw();
        $this->assertEquals(0, $response->items()['results'][0]['found']);
        $this->assertEquals(0, $response->items()['results'][1]['found']);
    }
}
