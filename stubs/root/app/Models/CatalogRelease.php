<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogRelease extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'locale',
        'primary_group',
        'schema_version',
        'version',
        'status',
        'payload_hash',
        'payload_path',
        'item_count',
        'filters',
        'metadata',
        'payload',
        'exported_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'item_count' => 'integer',
            'filters' => 'array',
            'metadata' => 'array',
            'payload' => 'array',
            'exported_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
