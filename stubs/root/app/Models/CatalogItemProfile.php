<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, string>|null $aliases
 * @property array<string, mixed>|null $facts
 * @property array<string, mixed>|null $links
 * @property array<string, mixed>|null $media
 * @property array<string, mixed>|null $seo_payload
 */
class CatalogItemProfile extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'product_type',
        'segment',
        'theme_profile',
        'image',
        'icon',
        'thumbnail',
        'version',
        'release_status',
        'development_status',
        'media',
        'links',
        'facts',
        'aliases',
        'seo_payload',
    ];

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'links' => 'array',
            'facts' => 'array',
            'aliases' => 'array',
            'seo_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
