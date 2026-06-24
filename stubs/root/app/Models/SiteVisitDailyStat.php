<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisitDailyStat extends Model
{
    protected $fillable = [
        'visit_date',
        'locale',
        'path',
        'path_hash',
        'route_name',
        'page_type',
        'catalog_item_id',
        'blog_post_id',
        'source_key',
        'source_type',
        'country_code',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'views',
        'unique_visitors',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'views' => 'integer',
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
