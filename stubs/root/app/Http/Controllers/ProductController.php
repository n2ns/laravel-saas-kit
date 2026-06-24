<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Services\ProductCatalogReader;
use App\Services\ProductService;
use App\Support\LocaleProfile;

class ProductController extends Controller
{
    private const TEMPLATE_VIEWS = [
        'product-detail-v1' => 'catalog.templates.product-detail',
        'developer-tool-detail-v1' => 'catalog.templates.developer-tool',
    ];

    public function __construct(
        protected ProductService $productService,
        protected ProductCatalogReader $catalogReader
    ) {}

    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = $this->productService->getProducts();

        return view('products.index', compact('products'));
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $catalogItem = $this->catalogReader->publicDetailItem($slug, $locale)
            ?? abort(404);

        $canonicalPath = $this->catalogReader->publicPath($catalogItem);
        if ($canonicalPath !== "/{$slug}") {
            abort(404);
        }

        $product = $catalogItem->product;

        if ($product && ! $product->is_active) {
            abort(404, 'Product configuration not found.');
        }

        if ($product) {
            $product->setRelation('catalogItem', $catalogItem);
        }

        $contentLocale = $catalogItem->detailContentLocaleFor($locale);
        request()->attributes->set('seo_content_locale', $contentLocale);

        $config = $this->catalogDetailConfig($catalogItem, $contentLocale);
        if (empty($config['sections'])) {
            abort(404, 'Product configuration not found.');
        }

        $guides = $product
            ? BlogPost::with('author')
                ->published()
                ->productGuides($product->code)
                ->withLocalizedTranslations($contentLocale)
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get()
            : collect();

        $viewData = [
            'product' => $product,
            'catalogItem' => $catalogItem,
            'config' => $config,
            'sectionsContent' => $this->catalogSectionsContent($catalogItem, $contentLocale),
            'slug' => $slug,
            'guides' => $guides,
            'hasPrivacyPage' => $this->catalogReader->hasPrivacyPolicy($catalogItem),
            'seoContentLocale' => $contentLocale,
            'seoCanonicalUrl' => localized_route('catalog.show', ['locale' => $contentLocale, 'slug' => $slug]),
            'seoAlternates' => $this->catalogAlternates('catalog.show', ['slug' => $slug], $catalogItem->renderableDetailLocales()),
        ];

        return view($this->catalogTemplateView($catalogItem), $viewData);
    }

    /**
     * Resolve a catalog item's privacy policy content for the given locale,
     * falling back to the default locale. Shape: ['updated' => string, 'sections' => array].
     *
     * @return array<string, mixed>
     */
    private function privacyContent(CatalogItem $catalogItem, string $locale): array
    {
        return $this->catalogReader->privacyContent($catalogItem, $locale);
    }

    public function privacy(string $slug)
    {
        $locale = app()->getLocale();

        $catalogItem = $this->catalogReader->publicDetailItem($slug, $locale)
            ?? abort(404);

        $catalogItem->loadMissing('privacyPolicies');

        $contentLocale = $catalogItem->privacyContentLocaleFor($locale);
        $privacy = $this->privacyContent($catalogItem, $contentLocale);
        if (empty($privacy['sections'] ?? null)) {
            return redirect(localized_route('catalog.show', ['locale' => $locale, 'slug' => $slug]), 301);
        }

        request()->attributes->set('seo_content_locale', $contentLocale);

        return view('catalog.product-privacy', [
            'catalogItem' => $catalogItem,
            'slug' => $slug,
            'privacy' => $privacy,
            'seoContentLocale' => $contentLocale,
            'seoCanonicalUrl' => localized_route('catalog.privacy', ['locale' => $contentLocale, 'slug' => $slug]),
            'seoAlternates' => $this->catalogAlternates('catalog.privacy', ['slug' => $slug], $catalogItem->renderablePrivacyLocales()),
        ]);
    }

    /**
     * @return array{hero: array<string, mixed>, sections: array<int, mixed>}
     */
    private function catalogDetailConfig(CatalogItem $catalogItem, string $locale): array
    {
        $detailPayload = $this->catalogReader->detailPayload($catalogItem);
        $sectionBlueprint = is_array($detailPayload['section_blueprint'] ?? null) ? $detailPayload['section_blueprint'] : [];
        $sections = is_array($detailPayload['sections'] ?? null) ? $detailPayload['sections'] : [];
        $facts = $this->catalogReader->profileValue($catalogItem, 'facts');

        return [
            'hero' => array_merge(is_array($sectionBlueprint['hero'] ?? null) ? $sectionBlueprint['hero'] : [], [
                'tag' => $catalogItem->getLocalized('card_tag', $locale),
                'title' => $catalogItem->getLocalized('name', $locale),
                'subtitle' => $catalogItem->getLocalized('short_description', $locale),
                'product_type' => $this->catalogReader->profileValue($catalogItem, 'product_type'),
                'version' => $this->catalogReader->profileValue($catalogItem, 'version'),
                'release_status' => $this->catalogReader->profileValue($catalogItem, 'release_status'),
                'product_tags' => $catalogItem->getLocalized('tags', $locale) ?? [],
                'metadata' => data_get($facts, 'metadata', []),
            ]),
            'sections' => $sections,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogSectionsContent(CatalogItem $catalogItem, string $locale): array
    {
        return $this->catalogReader->detailSectionsContent($catalogItem, $locale);
    }

    /**
     * @param  array<string, string>  $parameters
     * @param  array<int, string>  $locales
     * @return array<string, string>
     */
    private function catalogAlternates(string $routeName, array $parameters, array $locales): array
    {
        $alternates = [];
        foreach ($locales as $locale) {
            $alternates[LocaleProfile::hreflangFor($locale)] = localized_route(
                $routeName,
                $parameters + ['locale' => $locale]
            );
        }

        $defaultLocale = LocaleProfile::default();
        $xDefaultLocale = in_array($defaultLocale, $locales, true)
            ? $defaultLocale
            : ($locales[0] ?? null);

        if ($xDefaultLocale !== null) {
            $alternates['x-default'] = localized_route(
                $routeName,
                $parameters + ['locale' => $xDefaultLocale]
            );
        }

        return $alternates;
    }

    private function catalogTemplateView(CatalogItem $catalogItem): string
    {
        $templateKey = $catalogItem->detail?->template_key;

        return self::TEMPLATE_VIEWS[$templateKey] ?? abort(404);
    }
}
