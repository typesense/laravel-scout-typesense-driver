[![Latest Version on Packagist](https://img.shields.io/packagist/v/devloopsnet/laravel-typesense.svg?style=for-the-badge)](https://packagist.org/packages/devloopsnet/laravel-typesense) ![Postcardware](https://img.shields.io/badge/Postcardware-%F0%9F%92%8C-197593?style=for-the-badge)

[![PHP from Packagist](https://img.shields.io/packagist/php-v/devloopsnet/laravel-typesense?style=flat-square)](https://packagist.org/packages/devloopsnet/laravel-typesense) [![Total Downloads](https://img.shields.io/packagist/dt/devloopsnet/laravel-typesense.svg?style=flat-square)](https://packagist.org/packages/devloopsnet/laravel-typesense)

# Laravel Scout Typesense Engine

Typesense engine for laravel/scout https://github.com/typesense/typesense .

<p align="center">
    <img src="https://banners.beyondco.de/Typesense%20Driver%20for%20Laravel-Scout.png?theme=dark&packageName=devloopsnet%2Flaravel-typesense&pattern=anchorsAway&style=style_1&description=A+Typesense+%28search+engine%29+driver+for+laravel-scout&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg" alt="laravel-scout-typesense-engine
 socialcard">
</p>
This package makes it easy to add full text search support to your models with Laravel 7.* to 8.*.

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [Author](#author)
- [License](#license)

## Installation

You can install the package via composer:

``` bash
composer require devloopsnet/laravel-typesense
```

Add the service provider:

```php
// config/app.php
'providers' => [
    // ...
    Devloops\LaravelTypesense\TypesenseServiceProvider::class,
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
`Searchable` trait to your models that you want to make searchable. Additionaly, define the fields you want to make searchable by defining the `toSearchableArray` method on the model and implement `TypesenseSearch`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Devloops\LaravelTypesense\Interfaces\TypesenseSearch;
use Laravel\Scout\Searchable;

class Post extends Model implements TypesenseSearch
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

```php
$search = Post::search('Bugs Bunny');
```

### Or

```php
$search = Post::search('Bugs Bunny',function (\Laravel\Scout\Builder $builder,\Typesense\Documents $documents, string $query, array $params){
    return $documents->search($params);
});
```

Then you can apply your where(s) to the builder as follows :

```php

//This way the default operator := will be used
$search->where('created_at', now()->unix());

//Or specially for typesense engine you can add typesense operator to the where statement
$search->where('created_at', [
    '>=',
    now()->unix() 
]);

```

*Note : For geolocation search, make sure to send an empty operator as follows

```php
$search->where('location', [
    '',
    [
        48.86093481609114, 
        2.33698396872901
    ]    
]);

```

## Extended/Added methods to Scout Builder

#### Check [Typesense Search](https://typesense.org/docs/0.21.0/api/documents.html#search) for reference.

- Group by

```php
$search->groupBy(['name', 'created_at'])
//or
$search->groupBy('name', 'created_at')
```

- Order

```php
$search->orderBy('name','desc')
```

- Location Order

```php
$search->orderByLocation('location',48.853, 2.344, '1km')
//or
$search->orderByLocation('location',48.853, 2.344, '1mi')

//to allow ordering with other columns
$search->orderByLocation('location',48.853, 2.344, '1mi','asc',true)
```

- Group by limit

```php
$search->groupByLimit(200)
```

- Highlight start tag

```php
$search->setHighlightStartTag('<strong>')
```

- Highlight end tag

```php
$search->setHighlightEndTag('<end>')
```

- Hits limit

```php
$search->limitHits(200)
```

## Adding via Query

The `searchable()` method will chunk the results of the query and add the records to your search index.

```php
$post = Post::find(1);
```

### You may also add record via collection...

```php
$post->searchable();
```

#### ---- OR

```php
$posts = Post::where('year', '>', '2018')->get();
```

You may also add records via collections...

```php
$posts->searchable();
```

## Author

- [Abdullah Al-Faqeir](https://github.com/abdullahfaqeir)
- [Contributors](https://github.com/devloopsnet/laravel-scout-typesense-engine/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
