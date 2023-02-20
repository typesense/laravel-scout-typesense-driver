<?php

namespace Typesense\LaravelTypesense\Tests\Feature;

use Typesense\LaravelTypesense\Tests\Fixtures\UserModel;
use Typesense\LaravelTypesense\Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_search()
    {
        $models = UserModel::search('taylor@laravel.com')->get();

        $this->assertCount(1, $models);
    }
}
