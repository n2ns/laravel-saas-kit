<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteEventUnique extends Model
{
    protected $fillable = [
        'event_date',
        'locale',
        'event_key',
        'visitor_hash',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }
}
