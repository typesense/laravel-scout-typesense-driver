<?php

namespace Typesense\LaravelTypesense\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Model;
use Laravel\Scout\Searchable;
use Typesense\LaravelTypesense\Interfaces\TypesenseDocument;

class UserModel extends Model implements TypesenseDocument
{
    use Searchable;

    protected $fillable = ['email', 'name', 'password'];

    public $timestamps = false;

    protected $table = 'users';

    public function toSearchableArray(): array
    {
        return array_merge(
            $this->toArray(),
           [
                'id' => (string)$this->id,
                'created_at' => $this->created_at->timestamp,
            ]
        );
    }

    public function getCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                    'facet' => true,
                ],
                [
                    'name' => 'name',
                    'type' => 'string',
                    'facet' => true,
                ],
                [
                    'name' => 'email',
                    'type' => 'string',
                    'facet' => true,
                ],
                [
                    'name' => 'created_at',
                    'type' => 'int64',
                    'facet' => true,
                ],
            ],
            'default_sorting_field' => 'created_at',
        ];
    }

    public function typesenseQueryBy(): array
    {
        return [
            'name',
            'email',
        ];
    }
}
