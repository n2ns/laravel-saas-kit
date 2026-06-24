<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $structure_payload
 */
class CatalogItemDetail extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'template_key',
        'schema_version',
        'structure_payload',
    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'structure_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return HasMany<CatalogItemDetailTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogItemDetailTranslation::class);
    }
}
