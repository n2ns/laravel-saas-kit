<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\CatalogItemDetail;
use App\Models\CatalogItemProfile;
use App\Models\CatalogItemTranslation;
use App\Models\CatalogTaxonomy;
use App\Models\CatalogTaxonomyTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function configure(): static
    {
        return $this->afterCreating(function (CatalogItem $item): void {
            CatalogItemProfile::query()->firstOrCreate(
                ['catalog_item_id' => $item->id],
                [
                    'product_type' => 'web',
                    'segment' => 'product',
                    'theme_profile' => $item->code,
                    'release_status' => 'stable',
                    'development_status' => 'launched',
                ]
            );

            CatalogItemDetail::query()->firstOrCreate(
                ['catalog_item_id' => $item->id],
                [
                    'template_key' => 'product-detail-v1',
                    'schema_version' => 1,
                    'structure_payload' => [
                        'section_blueprint' => [],
                        'sections' => [],
                    ],
                ]
            );

            CatalogItemTranslation::query()->firstOrCreate(
                [
                    'catalog_item_id' => $item->id,
                    'locale' => 'en',
                ],
                [
                    'name' => str($item->code)->replace('-', ' ')->title()->toString(),
                    'short_description' => 'Test catalog item.',
                    'seo_title' => str($item->code)->replace('-', ' ')->title()->toString(),
                ]
            );

            if (! $this->hasPrimaryGroup($item)) {
                $this->assignPrimaryGroup($item, 'application_product');
            }
        });
    }

    public function definition(): array
    {
        $code = $this->faker->unique()->slug(2);

        return [
            'code' => $code,
            'status' => CatalogItem::STATUS_PUBLISHED,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_visible' => true,
            'show_on_homepage' => false,
            'homepage_sort_order' => null,
            'published_at' => now(),
        ];
    }

    public function primaryGroup(string $groupCode): static
    {
        return $this->afterCreating(function (CatalogItem $item) use ($groupCode): void {
            $this->assignPrimaryGroup($item, $groupCode);
        });
    }

    public function applicationProduct(): static
    {
        return $this->primaryGroup('application_product');
    }

    public function developerTool(): static
    {
        return $this->primaryGroup('developer_tool')
            ->afterCreating(function (CatalogItem $item): void {
                $item->detail?->update(['template_key' => 'developer-tool-detail-v1']);
            });
    }

    private function assignPrimaryGroup(CatalogItem $item, string $groupCode): void
    {
        $taxonomy = CatalogTaxonomy::query()->firstOrCreate(
            ['code' => 'primary_group'],
            [
                'name' => 'Primary group',
                'selection_mode' => 'single',
                'is_public_filter' => true,
                'sort_order' => 0,
            ]
        );

        $term = CatalogTaxonomyTerm::query()->firstOrCreate(
            [
                'catalog_taxonomy_id' => $taxonomy->id,
                'code' => $groupCode,
            ],
            [
                'name' => str($groupCode)->replace('_', ' ')->title()->toString(),
                'is_public' => true,
                'sort_order' => 0,
            ]
        );

        $existingTermIds = CatalogTaxonomyTerm::query()
            ->where('catalog_taxonomy_id', $taxonomy->id)
            ->pluck('id')
            ->all();

        $item->taxonomyTerms()->detach($existingTermIds);
        $item->taxonomyTerms()->attach($term->id, [
            'catalog_taxonomy_id' => $taxonomy->id,
            'source' => 'factory',
            'note' => null,
        ]);
    }

    private function hasPrimaryGroup(CatalogItem $item): bool
    {
        return $item->taxonomyTerms()
            ->whereHas('taxonomy', fn ($query) => $query->where('code', 'primary_group'))
            ->exists();
    }
}
