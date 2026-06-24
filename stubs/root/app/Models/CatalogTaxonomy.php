<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogTaxonomy extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'selection_mode',
        'is_public_filter',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public_filter' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<CatalogTaxonomyTerm, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(CatalogTaxonomyTerm::class);
    }
}
