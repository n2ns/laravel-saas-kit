<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $code = 'test-'.$this->faker->unique()->slug;

        return [
            'catalog_item_id' => fn (array $attributes): int => CatalogItem::factory()->create([
                'code' => $attributes['code'] ?? $code,
                'is_visible' => (bool) ($attributes['is_active'] ?? true),
                'status' => ($attributes['is_active'] ?? true) ? CatalogItem::STATUS_PUBLISHED : CatalogItem::STATUS_ARCHIVED,
            ])->id,
            'code' => $code,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
