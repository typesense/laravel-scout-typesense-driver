# Laravel Scout Typesense Engine
Typesense engine for laravel/scout https://github.com/typesense/typesense .

This package makes it easy to add full text search support to your models with Laravel 5.3 to 7.0.

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

Add  `SCOUT_DRIVER=typesensesearch` to your `.env` file

Then you should publish `scout.php` configuration file to your config directory

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

In your `config/scout.php` add:

```php

'typesensesearch' => [
    'master_node' => [
      'host' => 'HOST',
      'port' => '8108',
      'protocol' => 'http',
      'api_key' => 'API_KEY',
    ],
    'enabled_read_replica' => FALSE,
    'read_replica_nodes' => [
      [
        'host' => 'HOST',
        'port' => '8108',
        'protocol' => 'http',
        'api_key' => 'API_KEY',
      ],
    ],
    'timeout' => 2,
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
        'name' => $this->getTable(),
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
The `searchable()` method will chunk the results of the query and add the records to your search index. 

`$post = Post::find(1);`

// You may also add record via collection...
`$post->searchable();`

// OR

`$posts = Post::where('year', '>', '2018')->get();`

// You may also add records via collections...
`$posts->searchable();`

## Author

- [Abdullah Al-Faqeir](https://github.com/abdullahfaqeir)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
