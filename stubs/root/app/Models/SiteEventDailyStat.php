<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteEventDailyStat extends Model
{
    protected $fillable = [
        'event_date',
        'locale',
        'event_name',
        'event_type',
        'path',
        'path_hash',
        'target_url',
        'target_hash',
        'catalog_item_id',
        'blog_post_id',
        'events',
        'unique_visitors',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'events' => 'integer',
            'unique_visitors' => 'integer',
        ];
    }

    /** @return BelongsTo<CatalogItem, $this> */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /** @return BelongsTo<BlogPost, $this> */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
