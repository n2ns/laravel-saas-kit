<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $detail_sections
 * @property array<string, mixed>|null $localized_payload
 */
class CatalogItemDetailTranslation extends Model
{
    protected $fillable = [
        'catalog_item_detail_id',
        'locale',
        'detail_sections',
        'localized_payload',
    ];

    protected function casts(): array
    {
        return [
            'detail_sections' => 'array',
            'localized_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogItemDetail, $this>
     */
    public function detail(): BelongsTo
    {
        return $this->belongsTo(CatalogItemDetail::class, 'catalog_item_detail_id');
    }
}
