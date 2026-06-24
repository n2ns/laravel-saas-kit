<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitUnique extends Model
{
    protected $fillable = [
        'visit_date',
        'locale',
        'path_hash',
        'source_key',
        'visitor_hash',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }
}
