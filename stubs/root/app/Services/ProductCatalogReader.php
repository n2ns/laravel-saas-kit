<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\CatalogItemDetail;
use App\Models\CatalogItemDetailTranslation;
use App\Models\CatalogItemPrivacyPolicy;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductCatalogReader
{
    /**
     * @return Collection<int, CatalogItem>
     */
    public function publicItems(?string $locale = null, ?string $search = null, bool $withContent = false): Collection
    {
        $locale = $this->normalizeLocale($locale);
        $relations = ['profile'];

        if ($withContent) {
            $relations = [
                ...$relations,
                'detail.translations',
                'privacyPolicies',
                'taxonomyTerms.taxonomy',
            ];
        }

        $items = CatalogItem::query()
            ->select('catalog_items.*')
            ->addSelect([
                'primary_group_code' => $this->taxonomyTermCodeSubquery('primary_group'),
                'platform_code' => $this->taxonomyTermCodeSubquery('platform'),
            ])
            ->published()
            ->visible()
            ->whereDoesntHave('taxonomyTerms', fn ($termQuery) => $termQuery
                ->where('catalog_taxonomy_terms.code', 'concept_project')
                ->whereHas('taxonomy', fn ($taxonomyQuery) => $taxonomyQuery->where('code', 'primary_group')))
            ->when($search, fn ($query, string $keyword) => $query->search($keyword))
            ->with($relations)
            ->withLocalizedTranslations($locale)
            ->orderByRaw("CASE WHEN primary_group_code = 'concept_project' THEN 1 ELSE 0 END")
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return $items;
    }

    /**
     * @return Collection<int, CatalogItem>
     */
    public function homepageItems(?string $locale = null, ?string $search = null, bool $withContent = false): Collection
    {
        $locale = $this->normalizeLocale($locale);
        $relations = ['profile'];

        if ($withContent) {
            $relations = [
                ...$relations,
                'detail.translations',
                'privacyPolicies',
                'taxonomyTerms.taxonomy',
            ];
        }

        return CatalogItem::query()
            ->select('catalog_items.*')
            ->addSelect([
                'primary_group_code' => $this->taxonomyTermCodeSubquery('primary_group'),
                'platform_code' => $this->taxonomyTermCodeSubquery('platform'),
            ])
            ->published()
            ->visible()
            ->where('show_on_homepage', true)
            ->whereDoesntHave('taxonomyTerms', fn ($termQuery) => $termQuery
                ->where('catalog_taxonomy_terms.code', 'concept_project')
                ->whereHas('taxonomy', fn ($taxonomyQuery) => $taxonomyQuery->where('code', 'primary_group')))
            ->when($search, fn ($query, string $keyword) => $query->search($keyword))
            ->with($relations)
            ->withLocalizedTranslations($locale)
            ->orderByRaw('homepage_sort_order IS NULL')
            ->orderBy('homepage_sort_order')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function publicDetailItem(string $slug, ?string $locale = null): ?CatalogItem
    {
        $locale = $this->normalizeLocale($locale);

        $item = CatalogItem::query()
            ->select('catalog_items.*')
            ->addSelect([
                'primary_group_code' => $this->taxonomyTermCodeSubquery('primary_group'),
                'platform_code' => $this->taxonomyTermCodeSubquery('platform'),
            ])
            ->published()
            ->visible()
            ->where('code', $slug)
            ->withExists(['privacyPolicies as has_privacy_policy'])
            ->with([
                'product' => fn ($query) => $query->withExists([
                    'plans as has_paid_plans' => fn ($planQuery) => $planQuery->where('tier', '!=', 'free'),
                ]),
                'profile',
                'detail.translations',
            ])
            ->withLocalizedTranslations($locale)
            ->first();

        if (! $item instanceof CatalogItem) {
            return null;
        }

        return $this->hasPublicDetailPage($item) ? $item : null;
    }

    public function hasPublicDetailPage(CatalogItem $item): bool
    {
        return in_array($this->primaryGroupCode($item), ['application_product', 'developer_tool'], true);
    }

    public function profileValue(CatalogItem $item, string $field): mixed
    {
        $profile = $item->relationLoaded('profile') ? $item->profile : $item->profile()->first();

        if ($profile !== null && $profile->getAttribute($field) !== null) {
            return $profile->getAttribute($field);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(CatalogItem $item): array
    {
        $detail = $item->relationLoaded('detail') ? $item->detail : $item->detail()->first();

        if ($detail instanceof CatalogItemDetail && is_array($detail->structure_payload)) {
            return $detail->structure_payload;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function detailSectionsContent(CatalogItem $item, string $locale): array
    {
        $contentLocale = $this->normalizeLocale($locale);
        $detailTranslation = $item->localizedDetailTranslation($contentLocale)
            ?? $item->localizedDetailTranslation(LocaleProfile::default());

        if ($detailTranslation instanceof CatalogItemDetailTranslation && is_array($detailTranslation->detail_sections)) {
            return $detailTranslation->detail_sections;
        }

        return [];
    }

    /**
     * @return array{title?: string|null, updated?: string|null, effective_date?: string|null, sections?: array<int, mixed>, metadata?: array<string, mixed>|null}
     */
    public function privacyContent(CatalogItem $item, string $locale): array
    {
        $contentLocale = $item->privacyContentLocaleFor($this->normalizeLocale($locale));
        $policy = $item->privacyPolicyFor($contentLocale)
            ?? $item->privacyPolicyFor(LocaleProfile::default());

        if ($policy instanceof CatalogItemPrivacyPolicy && ! empty($policy->sections)) {
            return [
                'title' => $policy->title,
                'updated' => $policy->updated_label,
                'effective_date' => $policy->effective_date?->toDateString(),
                'sections' => $policy->sections,
                'metadata' => $policy->metadata,
            ];
        }

        return [];
    }

    public function hasPrivacyPolicy(CatalogItem $item): bool
    {
        $hasPrivacyPolicy = $item->getAttributes()['has_privacy_policy'] ?? null;
        if ($hasPrivacyPolicy !== null) {
            return (bool) $hasPrivacyPolicy;
        }

        if ($item->relationLoaded('privacyPolicies')) {
            return $item->privacyPolicies->contains(fn (CatalogItemPrivacyPolicy $policy): bool => ! empty($policy->sections));
        }

        return $item->privacyPolicies()->exists();
    }

    /**
     * @return array<string, array<int, array{code: string, name: string, description: string|null}>>
     */
    public function taxonomyGroups(CatalogItem $item): array
    {
        $terms = $item->relationLoaded('taxonomyTerms') ? $item->taxonomyTerms : $item->taxonomyTerms()->with('taxonomy')->get();

        return $terms
            ->filter(fn ($term): bool => $term->taxonomy !== null)
            ->groupBy(fn ($term): string => (string) $term->taxonomy->code)
            ->map(fn ($group) => $group
                ->sortBy('sort_order')
                ->map(fn ($term): array => [
                    'code' => (string) $term->code,
                    'name' => (string) $term->name,
                    'description' => $term->description,
                ])
                ->values()
                ->all())
            ->all();
    }

    public function primaryGroupCode(CatalogItem $item): ?string
    {
        $primaryGroupCode = $item->getAttributes()['primary_group_code'] ?? null;
        if (is_string($primaryGroupCode) && $primaryGroupCode !== '') {
            return $primaryGroupCode;
        }

        return $this->taxonomyGroups($item)['primary_group'][0]['code'] ?? null;
    }

    public function platformCode(CatalogItem $item): ?string
    {
        $platformCode = $item->getAttributes()['platform_code'] ?? null;
        if (is_string($platformCode) && $platformCode !== '') {
            return $platformCode;
        }

        return $this->taxonomyGroups($item)['platform'][0]['code'] ?? null;
    }

    public function displayCategory(CatalogItem $item): string
    {
        return match ($this->primaryGroupCode($item)) {
            'application_product' => 'application-product',
            'developer_tool' => 'developer-tool',
            'concept_project' => 'concept',
            default => 'uncategorized',
        };
    }

    public function publicPath(CatalogItem $item): string
    {
        return '/'.trim($item->code, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function seoPayload(CatalogItem $item): array
    {
        $profileSeo = $this->profileValue($item, 'seo_payload');

        if (is_array($profileSeo)) {
            return $profileSeo;
        }

        return [];
    }

    private function normalizeLocale(?string $locale): string
    {
        if ($locale === 'zh') {
            return 'zh_CN';
        }

        if ($locale !== null && LocaleProfile::hasPrefix($locale)) {
            return config("app.supported_locales.{$locale}", LocaleProfile::default());
        }

        return LocaleProfile::normalize($locale);
    }

    private function taxonomyTermCodeSubquery(string $taxonomy): mixed
    {
        return DB::table('catalog_item_taxonomy_terms')
            ->join('catalog_taxonomy_terms', 'catalog_taxonomy_terms.id', '=', 'catalog_item_taxonomy_terms.catalog_taxonomy_term_id')
            ->join('catalog_taxonomies', 'catalog_taxonomies.id', '=', 'catalog_item_taxonomy_terms.catalog_taxonomy_id')
            ->whereColumn('catalog_item_taxonomy_terms.catalog_item_id', 'catalog_items.id')
            ->where('catalog_taxonomies.code', $taxonomy)
            ->orderBy('catalog_taxonomy_terms.sort_order')
            ->limit(1)
            ->select('catalog_taxonomy_terms.code');
    }
}
