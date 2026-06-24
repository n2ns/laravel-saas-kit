<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItemTaxonomyTerm extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'catalog_taxonomy_id',
        'catalog_taxonomy_term_id',
        'source',
        'note',
    ];

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return BelongsTo<CatalogTaxonomy, $this>
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(CatalogTaxonomy::class, 'catalog_taxonomy_id');
    }

    /**
     * @return BelongsTo<CatalogTaxonomyTerm, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(CatalogTaxonomyTerm::class, 'catalog_taxonomy_term_id');
    }
}
