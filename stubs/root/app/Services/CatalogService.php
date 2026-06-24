<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\CatalogItemTranslation;
use App\Models\CatalogRelease;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public const EXPORT_SCHEMA = 'catalog.export.v1';

    public function __construct(
        private readonly ProductCatalogReader $catalogReader
    ) {}

    /**
     * @param  array{primary_group?: string|null, locale?: string|null, q?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function export(array $filters = []): array
    {
        $requestedLocale = $filters['locale'] ?? LocaleProfile::default();
        $locale = $this->normalizeLocale($requestedLocale);
        $primaryGroup = $filters['primary_group'] ?? null;
        $search = $filters['q'] ?? null;

        $items = $this->items($primaryGroup, $locale, $search)
            ->map(fn (CatalogItem $item): array => $this->toExportItem($item, $locale))
            ->values()
            ->all();

        $payload = [
            'schema' => self::EXPORT_SCHEMA,
            'schema_version' => 1,
            'generated_at' => now()->toJSON(),
            'filters' => [
                'primary_group' => $primaryGroup,
                'locale' => $requestedLocale,
                'q' => $search,
            ],
            'locale' => [
                'requested' => $requestedLocale,
                'resolved' => $locale,
                'prefix' => LocaleProfile::prefixFor($locale),
                'hreflang' => LocaleProfile::hreflangFor($locale),
                'fallback' => LocaleProfile::default(),
            ],
            'items' => $items,
        ];

        $payload['release'] = [
            'version' => $this->releaseVersion(),
            'payload_hash' => $this->payloadHash($payload),
            'item_count' => count($items),
        ];

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $filters
     */
    public function recordRelease(array $payload, array $filters = [], ?string $payloadPath = null): CatalogRelease
    {
        return CatalogRelease::create([
            'locale' => isset($filters['locale']) ? $this->normalizeLocale($filters['locale']) : null,
            'primary_group' => $filters['primary_group'] ?? null,
            'schema_version' => $payload['schema_version'] ?? 1,
            'version' => $payload['release']['version'] ?? $this->releaseVersion(),
            'status' => CatalogRelease::STATUS_RELEASED,
            'payload_hash' => $payload['release']['payload_hash'] ?? $this->payloadHash($payload),
            'payload_path' => $payloadPath,
            'item_count' => count($payload['items'] ?? []),
            'filters' => $filters,
            'metadata' => [
                'schema' => $payload['schema'] ?? self::EXPORT_SCHEMA,
            ],
            'payload' => $payloadPath === null ? $payload : null,
            'exported_at' => now(),
            'released_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, CatalogItem>
     */
    public function items(?string $primaryGroup = null, ?string $locale = null, ?string $search = null): Collection
    {
        $locale = $this->normalizeLocale($locale);

        if ($primaryGroup === 'concept_project') {
            return new Collection;
        }

        return CatalogItem::query()
            ->published()
            ->visible()
            ->whereDoesntHave('taxonomyTerms', fn ($termQuery) => $termQuery
                ->where('catalog_taxonomy_terms.code', 'concept_project')
                ->whereHas('taxonomy', fn ($taxonomyQuery) => $taxonomyQuery->where('code', 'primary_group')))
            ->when($primaryGroup, fn ($query) => $query->whereHas(
                'taxonomyTerms',
                fn ($termQuery) => $termQuery
                    ->where('catalog_taxonomy_terms.code', $primaryGroup)
                    ->whereHas('taxonomy', fn ($taxonomyQuery) => $taxonomyQuery->where('code', 'primary_group'))
            ))
            ->when($search, fn ($query, string $keyword) => $query->search($keyword))
            ->with([
                'profile',
                'detail.translations',
                'privacyPolicies',
                'taxonomyTerms.taxonomy',
            ])
            ->withLocalizedTranslations($locale)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function toExportItem(CatalogItem $item, string $locale): array
    {
        $translation = $item->localizedTranslation($locale);
        $detailPayload = $this->catalogReader->detailPayload($item);
        $path = $this->catalogReader->hasPublicDetailPage($item) ? $this->catalogReader->publicPath($item) : null;
        $localizedPath = $this->localizedPath($path, $locale);
        $canonicalUrl = $this->absoluteUrl($localizedPath);
        $profileFacts = $this->catalogReader->profileValue($item, 'facts');

        return [
            'code' => $item->code,
            'primary_group' => $this->catalogReader->primaryGroupCode($item),
            'product_type' => $this->catalogReader->profileValue($item, 'product_type'),
            'segment' => $this->catalogReader->profileValue($item, 'segment'),
            'theme_profile' => $this->catalogReader->profileValue($item, 'theme_profile'),
            'status' => $item->status,
            'is_visible' => $item->is_visible,
            'show_on_homepage' => $item->show_on_homepage,
            'homepage_sort_order' => $item->homepage_sort_order,
            'release_status' => $this->catalogReader->profileValue($item, 'release_status'),
            'version' => $this->catalogReader->profileValue($item, 'version'),
            'sort_order' => $item->sort_order,
            'template_key' => $item->detail?->template_key,
            'schema_version' => $item->detail?->schema_version,
            'name' => $item->getLocalized('name', $locale),
            'short_description' => $item->getLocalized('short_description', $locale),
            'long_description' => $item->getLocalized('long_description', $locale),
            'card_tag' => $item->getLocalized('card_tag', $locale),
            'cta_label' => $item->getLocalized('cta_label', $locale),
            'tags' => $item->getLocalized('tags', $locale) ?? [],
            'key_points' => $item->getLocalized('key_points', $locale) ?? [],
            'media' => $this->catalogReader->profileValue($item, 'media') ?? [],
            'links' => $this->catalogReader->profileValue($item, 'links') ?? [],
            'facts' => $profileFacts ?? [],
            'aliases' => $this->catalogReader->profileValue($item, 'aliases') ?? [],
            'taxonomy' => $this->catalogReader->taxonomyGroups($item),
            'available_content' => [
                'detail' => $item->hasRenderableDetailContent($locale),
                'privacy_policy' => $item->hasRenderablePrivacyContent($locale),
                'detail_locales' => $item->renderableDetailLocales(),
                'privacy_policy_locales' => $item->renderablePrivacyLocales(),
            ],
            'path' => $path,
            'localized_path' => $localizedPath,
            'canonical_url' => $canonicalUrl,
            'seo' => $this->seoPayload($item, $translation, $locale, $path, $localizedPath, $canonicalUrl),
            'detail' => [
                'section_blueprint' => $detailPayload['section_blueprint'] ?? [],
                'sections' => $detailPayload['sections'] ?? [],
                'localized_sections' => $this->catalogReader->detailSectionsContent($item, $locale),
                'content_payload' => $detailPayload['content_payload'] ?? [],
            ],
        ];
    }

    public function normalizeLocale(?string $locale): string
    {
        if ($locale === 'zh') {
            return 'zh_CN';
        }

        if ($locale !== null && LocaleProfile::hasPrefix($locale)) {
            return config("app.supported_locales.{$locale}", LocaleProfile::default());
        }

        return LocaleProfile::normalize($locale);
    }

    private function localizedPath(?string $path, string $locale): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $prefix = LocaleProfile::prefixFor($locale);
        if ($locale === LocaleProfile::default()) {
            $prefix = '';
        }

        return '/'.trim($prefix.'/'.trim($path, '/'), '/');
    }

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function seoPayload(
        CatalogItem $item,
        ?CatalogItemTranslation $translation,
        string $locale,
        ?string $path,
        ?string $localizedPath,
        ?string $canonicalUrl
    ): array {
        $itemSeo = $this->catalogReader->seoPayload($item);
        $translationSeo = $translation instanceof CatalogItemTranslation
            ? $this->arrayValue($translation->seo_payload)
            : [];
        $seo = array_merge($itemSeo, $translationSeo);

        return array_merge($seo, [
            'title' => $item->getLocalized('seo_title', $locale) ?: $item->getLocalized('name', $locale),
            'description' => $item->getLocalized('seo_description', $locale) ?: $item->getLocalized('short_description', $locale),
            'canonical_path' => $path,
            'localized_path' => $localizedPath,
            'canonical_url' => $canonicalUrl,
        ]);
    }

    private function releaseVersion(): string
    {
        return now()->utc()->format('YmdHis');
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
