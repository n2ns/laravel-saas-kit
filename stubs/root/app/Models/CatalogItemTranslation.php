<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $seo_payload
 */
class CatalogItemTranslation extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'locale',
        'name',
        'short_description',
        'long_description',
        'seo_title',
        'seo_description',
        'card_tag',
        'cta_label',
        'tags',
        'key_points',
        'seo_payload',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'key_points' => 'array',
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
