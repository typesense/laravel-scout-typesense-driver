# Laravel Scout Typesense Driver 

This package makes it easy to add full text search support to your models with Laravel 7.\* to 11.\*. 

> [!IMPORTANT] 
> The features from the Scout driver in this repo have been merged upstream into [Laravel Scout natively](https://laravel.com/docs/11.x/scout#typesense).
> 
> So we've temporarily paused development in this repo and plan to instead address any issues or improvements in the native [Laravel Scout](https://github.com/laravel/scout) driver instead.
> 
> If there are any Typesense-specific features that would be hard to implement in Laravel Scout natively (since we need to maintain consistency with all the other drivers), then at that point we plan to add those features into this driver and maintain it as a "Scout Extended Driver" of sorts. But it's too early to tell if we'd want to do this, so we're in a holding pattern on this repo for now.
> 
> In the meantime, we recommend switching to the native Laravel Scout driver and report any issues in the [Laravel Scout repo](https://github.com/laravel/scout).

<!--

[![Latest Version on Packagist](https://img.shields.io/packagist/v/typesense/laravel-scout-typesense-driver.svg?style=flat-square)](https://packagist.org/packages/typesense/laravel-scout-typesense-driver) [![PHP from Packagist](https://img.shields.io/packagist/php-v/typesense/laravel-scout-typesense-driver?style=flat-square)](https://packagist.org/packages/typesense/laravel-scout-typesense-driver)

-->

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [Migrating from devloopsnet/laravel-typesense](#migrating-from-devloopsnetlaravel-typesense)
- [Authors](#authors)
- [License](#license)


## Installation
The Typesense PHP SDK uses httplug to interface with various PHP HTTP libraries through a single API. 

First, install the correct httplug adapter based on your `guzzlehttp/guzzle` version. For example, if you're on 
Laravel 8, which includes Guzzle 7, then run this:

```bash
composer require php-http/guzzle7-adapter
```

Then install the driver:

```bash
composer require typesense/laravel-scout-typesense-driver
```

And add the service provider:

```php
// config/app.php
'providers' => [
    // ...
    Typesense\LaravelTypesense\TypesenseServiceProvider::class,
],
```

Ensure you have Laravel Scout as a provider too otherwise you will get an "unresolvable dependency" error

```php
// config/app.php
'providers' => [
    // ...
    Laravel\Scout\ScoutServiceProvider::class,
],
```

Add `SCOUT_DRIVER=typesense` to your `.env` file

Then you should publish `scout.php` configuration file to your config directory

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

In your `config/scout.php` add:

```php

'typesense' => [
    'api_key'         => 'abcd',
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
  ],
```

## Usage

If you are unfamiliar with Laravel Scout, we suggest reading it's [documentation](https://laravel.com/docs/11.x/scout) first.

After you have installed scout and the Typesense driver, you need to add the
`Searchable` trait to your models that you want to make searchable. Additionaly,
define the fields you want to make searchable by defining the `toSearchableArray` method on the model and implement `TypesenseSearch`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Typesense\LaravelTypesense\Interfaces\TypesenseDocument;
use Laravel\Scout\Searchable;

class Todo extends Model implements TypesenseDocument
{
    use Searchable;
    
     /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return array_merge(
            $this->toArray(), 
            [
                // Cast id to string and turn created_at into an int32 timestamp
                // in order to maintain compatibility with the Typesense index definition below
                'id' => (string) $this->id,
                'created_at' => $this->created_at->timestamp,
            ]
        );
    }

     /**
     * The Typesense schema to be created.
     *
     * @return array
     */
    public function getCollectionSchema(): array {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
                [
                    'name' => 'name',
                    'type' => 'string',
                ],
                [
                    'name' => 'created_at',
                    'type' => 'int64',
                ],
            ],
            'default_sorting_field' => 'created_at',
        ];
    }

     /**
     * The fields to be queried against. See https://typesense.org/docs/0.24.0/api/search.html.
     *
     * @return array
     */
    public function typesenseQueryBy(): array {
        return [
            'name',
        ];
    }    
}
```

Then, sync the data with the search service like:

`php artisan scout:import App\\Models\\Todo`

After that you can search your models with:

`Todo::search('Test')->get();`

## Adding via Query
The `searchable()` method will chunk the results of the query and add the records to your search index. Examples:

```php
$todo = Todo::find(1);
$todo->searchable();

$todos = Todo::where('created_at', '<', now())->get();
$todos->searchable();
```

### Multi Search
You can send multiple search requests in a single HTTP request, using the Multi-Search feature.
```php
$searchRequests = [
    [
      'collection' => 'todo',
      'q' => 'todo'
    ],
    [
      'collection' => 'todo',
      'q' => 'foo'
    ]
];

Todo::search('')->searchMulti($searchRequests)->paginateRaw();
```

### Generate Scoped Search Key

You can generate scoped search API keys that have embedded search parameters in them. This is useful in a few different scenarios:
1. You can index data from multiple users/customers in a single Typesense collection (aka multi-tenancy) and create scoped search keys with embedded `filter_by` parameters that only allow users access to their own subset of data.
2. You can embed any [search parameters](https://typesense.org/docs/0.24.0/api/search.html#search-parameters) (for eg: `exclude_fields` or `limit_hits`) to prevent users from being able to modify it client-side.

When you use these scoped search keys in a search API call, the parameters you embedded in them will be automatically applied by Typesense and users will not be able to override them.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Typesense\LaravelTypesense\Concerns\HasScopedApiKey;
use Typesense\LaravelTypesense\Interfaces\TypesenseDocument;

class Todo extends Model implements TypesenseDocument
{
    use Searchable, HasScopedApiKey;
}
```

#### Usage
```php
Todo::setScopedApiKey('xyz')->search('todo')->get();
```

## Migrating from devloopsnet/laravel-typesense
- Replace `devloopsnet/laravel-typesense` in your composer.json requirements with `typesense/laravel-scout-typesense-driver`
- The Scout driver is now called `typesense`, instead of `typesensesearch`. This should be reflected by setting the SCOUT_DRIVER env var to `typesense`,
  and changing the config/scout.php config key from `typesensesearch` to `typesense`
- Instead of importing `Devloops\LaravelTypesense\*`, you should import `Typesense\LaravelTypesense\*`
- Instead of models implementing `Devloops\LaravelTypesense\Interfaces\TypesenseSearch`, they should implement `Typesense\LaravelTypesense\Interfaces\TypesenseDocument`

## Authors
This package was originally authored by [Abdullah Al-Faqeir](https://github.com/AbdullahFaqeir) and his company DevLoops: https://github.com/devloopsnet/laravel-scout-typesense-engine. It has since been adopted into the Typesense Github org. 

Other key contributors include:

- [hi019](https://github.com/hi019)
- [Philip Manavopoulos](https://github.com/manavo)

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
