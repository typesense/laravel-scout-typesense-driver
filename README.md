**Note: This library is in the process of being adopted into the Typesense Github org. Follow [this issue](https://github.com/typesense/laravel-scout-typesense-engine/issues/1) for updates. In the meantime, please continue to use [the original library](https://github.com/devloopsnet/laravel-scout-typesense-engine) instead.**

---

<!--

[![Latest Version on Packagist](https://img.shields.io/packagist/v/typesense/laravel-typesense.svg?style=for-the-badge)](https://packagist.org/packages/typesense/laravel-typesense)

[![PHP from Packagist](https://img.shields.io/packagist/php-v/typesense/laravel-typesense?style=flat-square)](https://packagist.org/packages/typesense/laravel-typesense) [![Total Downloads](https://img.shields.io/packagist/dt/typesense/laravel-typesense.svg?style=flat-square)](https://packagist.org/packages/typesense/laravel-typesense)

-->

# Laravel Scout Typesense Engine
<p align="center">
    <img src="https://banners.beyondco.de/typesense%2Flaravel-typesense.png?theme=dark&packageManager=composer+require&packageName=typesense%2Flaravel-typesense&pattern=architect&style=style_1&description=Easy+Typesense+support+for+Laravel+Scout&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg">
</p>

This package makes it easy to add full text search support to your models with Laravel 7.\* to 8.\*. 

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [Migrating from devloopsnet/laravel-typesense](#migrating-from-devloopsnetlaravel-typesense)
- [Authors](#authors)
- [License](#license)


## Installation

You can install the package via composer:

``` bash
composer require typesense/laravel-typesense
```

Add the service provider:

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

Add  `SCOUT_DRIVER=typesense` to your `.env` file

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

After you have installed scout and the Typesense driver, you need to add the
`Searchable` trait to your models that you want to make searchable. Additionaly,
define the fields you want to make searchable by defining the `toSearchableArray` method on the model and implement `TypesenseSearch`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Typesense\LaravelTypesense\Interfaces\TypesenseDocument;
use Laravel\Scout\Searchable;

class Post extends Model implements TypesenseDocument
{
    use Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...

        return $array;
    }

    public function getCollectionSchema(): array {
      return [
        'name' => $this->searchableAs(),
        'fields' => [
          [
            'name' => 'title',
            'type' => 'string',
          ],
          [
            'name' => 'created_at',
            'type' => 'int32',
          ],
        ],
        'default_sorting_field' => 'created_at',
      ];
    }

    public function typesenseQueryBy(): array {
      return [
        'name',
      ];
    }
    
}
```

Then, sync the data with the search service like:

`php artisan scout:import App\\Post`

After that you can search your models with:

`Post::search('Bugs Bunny')->get();`

## Adding via Query
The `searchable()` method will chunk the results of the query and add the records to your search index. Examples:

```php
$post = Post::find(1);
$post->searchable();

$posts = Post::where('year', '>', '2018')->get();
$posts->searchable();
```

## Migrating from devloopsnet/laravel-typesense
- Replace `devloopsnet/laravel-typesense` in your composer.json requirements with `typesense/laravel-typesense`
- The Scout driver is now called `typesense`, instead of `typesensesearch`. This should be reflected by setting the SCOUT_DRIVER env var to `typesense`,
  and changing the config/scout.php config key from `typesensesearch` to `typesense`
- Instead of importing `Devloops\LaravelTypesense\*`, you should import `Typesense\LaravelTypesense\*`
- Instead of models implementing `Devloops\LaravelTypesense\Interfaces\TypesenseSearch`, they should implement `Typesense\LaravelTypesense\Interfaces\TypesenseDocument`
- In the rare case where the `TypesenseEngine` method `delete` is called directly, all the models passed to the method must now belong to the same Typesense index

## Authors
This package was based off of https://github.com/AbdullahFaqeir and his company DevLoops' work, https://github.com/devloopsnet/laravel-scout-typesense-engine. Other contributors include:

- [hi019](https://github.com/hi019)

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
