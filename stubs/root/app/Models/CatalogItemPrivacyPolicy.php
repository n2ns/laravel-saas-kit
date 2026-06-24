<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, array<string, mixed>>|null $sections
 * @property array<string, mixed>|null $metadata
 */
class CatalogItemPrivacyPolicy extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'locale',
        'title',
        'updated_label',
        'effective_date',
        'sections',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'sections' => 'array',
            'metadata' => 'array',
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
