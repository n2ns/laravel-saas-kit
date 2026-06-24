<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property array<int, string>|null $examples
 * @property array<int, string>|null $negative_examples
 */
class CatalogTaxonomyTerm extends Model
{
    protected $fillable = [
        'catalog_taxonomy_id',
        'code',
        'name',
        'description',
        'ai_definition',
        'examples',
        'negative_examples',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'examples' => 'array',
            'negative_examples' => 'array',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogTaxonomy, $this>
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(CatalogTaxonomy::class, 'catalog_taxonomy_id');
    }

    /**
     * @return BelongsToMany<CatalogItem, $this>
     */
    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_taxonomy_terms')
            ->withPivot(['catalog_taxonomy_id', 'source', 'note'])
            ->withTimestamps();
    }
}
