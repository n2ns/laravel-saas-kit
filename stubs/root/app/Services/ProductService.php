<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Plan;
use App\Models\Product;

class ProductService
{
    private const TYPE_ICON_MAP = [
        'mcp' => 'server',
        'vscode' => 'code-2',
        'jetbrains' => 'terminal',
        'ide' => 'zap',
        'chrome' => 'globe',
        'web' => 'globe',
        'desktop' => 'desktop',
        'server' => 'server',
        'mobile' => 'smartphone',
    ];

    private const THEME_ICON_MAP = [
        'starter' => 'globe',
    ];

    private const TYPE_COLOR_MAP = [
        'mcp' => 'emerald',
        'vscode' => 'blue',
        'jetbrains' => 'amber',
        'ide' => 'primary',
        'chrome' => 'cyan',
        'web' => 'cyan',
        'desktop' => 'slate',
        'server' => 'slate',
        'mobile' => 'rose',
        'starter' => 'cyan',
    ];

    private const THEME_COLOR_MAP = [
        'starter' => 'cyan',
    ];

    private const DEFAULT_SEGMENT = 'product';

    public function __construct(
        private readonly ProductCatalogReader $catalogReader
    ) {}

    /**
     * Get all visible catalog items formatted for public display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProducts(): array
    {
        $locale = app()->getLocale();
        $items = $this->catalogReader->publicItems(locale: $locale);

        $items->load([
            'product.plans',
        ]);

        return $items
            ->map(fn (CatalogItem $item): array => $this->formatCatalogItem($item, $locale))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedProducts(): array
    {
        return $this->getHomepageProducts();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHomepageProducts(): array
    {
        $locale = app()->getLocale();
        $items = $this->catalogReader->homepageItems(locale: $locale);

        $items->load([
            'product.plans',
        ]);

        return $items
            ->reject(fn (CatalogItem $item): bool => $this->catalogReader->primaryGroupCode($item) === 'concept_project')
            ->map(fn (CatalogItem $item): array => $this->formatCatalogItem($item, $locale))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCompanyProducts(): array
    {
        return array_values(array_filter($this->getProducts(), fn (array $product): bool => (bool) ($product['is_product'] ?? false)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDeveloperToolProducts(): array
    {
        return array_values(array_filter($this->getProducts(), fn (array $product): bool => (bool) ($product['is_developer_tool'] ?? false)));
    }

    public function isDeveloperToolCode(string $code): bool
    {
        $item = $this->catalogReader->publicDetailItem($code, app()->getLocale());

        return $item instanceof CatalogItem
            && $this->catalogReader->primaryGroupCode($item) === 'developer_tool';
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCatalogItem(CatalogItem $item, string $locale): array
    {
        $product = $item->product;
        $primaryGroup = $this->catalogReader->primaryGroupCode($item);
        $isProduct = $primaryGroup === 'application_product';
        $isDeveloperTool = $primaryGroup === 'developer_tool';
        $isConcept = $primaryGroup === 'concept_project';
        $themeProfile = $this->catalogReader->profileValue($item, 'theme_profile') ?? $item->code;
        $productType = $this->catalogReader->profileValue($item, 'product_type');
        $media = $this->catalogReader->profileValue($item, 'media');
        $links = $this->catalogReader->profileValue($item, 'links');
        $facts = $this->catalogReader->profileValue($item, 'facts');
        $link = $this->resolveCatalogLink($item, $product, $locale);

        return [
            'id' => $item->code,
            'slug' => $item->code,
            'title' => (string) ($item->getLocalized('name', $locale) ?? $item->code),
            'description' => (string) ($item->getLocalized('short_description', $locale) ?? ''),
            'tag' => (string) ($item->getLocalized('card_tag', $locale) ?? ''),
            'image' => $this->catalogReader->profileValue($item, 'image') ?? data_get($media, 'image') ?? 'images/products/starter_banner.svg',
            'link' => $link,
            'is_external' => $this->isExternalLink($link),
            'external_url' => $this->isExternalLink($link) ? $link : null,
            'theme_profile' => $themeProfile,
            'pricing_link' => $isProduct && $product && $this->hasActivePaidPlans($product) ? $this->productPricingPath($product, $locale) : null,
            'pricing_label' => (string) ($item->getLocalized('cta_label', $locale) ?? 'View Plans'),
            'color' => $this->resolveThemeColor($themeProfile, $productType),
            'icon' => $this->catalogReader->profileValue($item, 'icon') ?: $this->resolveThemeIcon($themeProfile, $productType),
            'product' => $product,
            'segment' => $this->catalogReader->profileValue($item, 'segment') ?? self::DEFAULT_SEGMENT,
            'display_category' => $this->catalogReader->displayCategory($item),
            'taxonomy' => $item->relationLoaded('taxonomyTerms') ? $this->catalogReader->taxonomyGroups($item) : [],
            'version' => $this->catalogReader->profileValue($item, 'version'),
            'badges' => data_get($facts, 'metadata.badges', []),
            'github_url' => $isConcept ? null : data_get($links, 'sources.github.url'),
            'is_product' => $isProduct,
            'is_developer_tool' => $isDeveloperTool,
            'is_concept' => $isConcept,
        ];
    }

    private function hasActivePaidPlans(Product $product): bool
    {
        if ($product->relationLoaded('plans')) {
            return $product->plans->contains(
                fn (Plan $plan): bool => $plan->is_active && (float) $plan->price > 0
            );
        }

        return $product->plans()
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->exists();
    }

    private function resolveThemeColor(string $themeProfile, ?string $productType): string
    {
        if (isset(self::THEME_COLOR_MAP[$themeProfile])) {
            return self::THEME_COLOR_MAP[$themeProfile];
        }

        if (isset(self::TYPE_COLOR_MAP[$themeProfile])) {
            return self::TYPE_COLOR_MAP[$themeProfile];
        }

        if ($productType !== null && isset(self::TYPE_COLOR_MAP[$productType])) {
            return self::TYPE_COLOR_MAP[$productType];
        }

        return 'slate';
    }

    private function resolveThemeIcon(string $themeProfile, ?string $productType): string
    {
        if (isset(self::THEME_ICON_MAP[$themeProfile])) {
            return self::THEME_ICON_MAP[$themeProfile];
        }

        if (isset(self::TYPE_ICON_MAP[$themeProfile])) {
            return self::TYPE_ICON_MAP[$themeProfile];
        }

        if ($productType !== null && isset(self::TYPE_ICON_MAP[$productType])) {
            return self::TYPE_ICON_MAP[$productType];
        }

        return 'package';
    }

    private function resolveCatalogLink(CatalogItem $item, ?Product $product, string $locale): string
    {
        $primaryGroup = $this->catalogReader->primaryGroupCode($item);

        if ($primaryGroup === 'developer_tool') {
            return localized_route('catalog.show', ['slug' => $item->code, 'locale' => $locale], false);
        }

        if ($primaryGroup === 'application_product') {
            return localized_route('catalog.show', ['slug' => $item->code, 'locale' => $locale], false);
        }

        return '#';
    }

    private function isExternalLink(string $link): bool
    {
        if (! str_starts_with($link, 'http://') && ! str_starts_with($link, 'https://')) {
            return false;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        return $appUrl === '' || ! str_starts_with($link, $appUrl.'/');
    }

    private function productPublicPath(Product $product, string $locale): string
    {
        return localized_route('catalog.show', ['slug' => $product->code, 'locale' => $locale], false);
    }

    private function productPricingPath(Product $product, string $locale): string
    {
        return localized_route('catalog.pricing', ['slug' => $product->code, 'locale' => $locale], false);
    }
}
