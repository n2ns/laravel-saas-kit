<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Models\Product;
use App\Services\ProductCatalogReader;
use App\Support\LocaleProfile;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemapCommand extends Command
{
    /** @var array<int, string> */
    private array $supportedLocales;

    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the sitemap with multilingual hreflang support';

    /**
     * All public pages that should be in the sitemap.
     *
     * @var array<string, float>
     */
    private array $pages = [
        '' => 1.0,           // Home
        'about' => 0.8,
        'products' => 0.9,
        'blog' => 0.7,
        'account-access' => 0.6,
        'get-started' => 0.6,
        'support' => 0.6,
        'privacy' => 0.3,
        'terms' => 0.3,
        'refund' => 0.3,
    ];

    /**
     * Supported locales.
     * Key = hreflang code, Value = URL prefix
     *
     * @var array<string, string>
     */
    private array $locales;

    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create();
        $baseUrl = config('app.url');

        // 1. Static Pages
        foreach ($this->pages as $path => $priority) {
            foreach ($this->locales as $hreflang => $urlPrefix) {
                $localizedPath = ($urlPrefix ? $urlPrefix : '').($path ? '/'.$path : '');
                $localizedUrl = rtrim($baseUrl, '/').'/'.ltrim($localizedPath, '/');

                $url = Url::create($localizedUrl)
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority($priority);

                // Add hreflang for all locale variants of this page
                foreach ($this->locales as $altHreflang => $altPrefix) {
                    $alternatePath = ($altPrefix ? $altPrefix : '').($path ? '/'.$path : '');
                    $alternateUrl = rtrim($baseUrl, '/').'/'.ltrim($alternatePath, '/');
                    $url->addAlternate($alternateUrl, $altHreflang);
                }

                // x-default always points to the default locale version
                $xDefaultPrefix = $this->defaultLocalePrefix();
                $xDefaultPath = ($xDefaultPrefix ? $xDefaultPrefix : '').($path ? '/'.$path : '');
                $xDefaultUrl = rtrim($baseUrl, '/').'/'.ltrim($xDefaultPath, '/');
                $url->addAlternate($xDefaultUrl, 'x-default');

                $sitemap->add($url);
            }
        }

        // 2. Dynamic Content
        $this->info('Adding Catalog Items...');
        $sitemap->add($this->catalogItemSitemapTags());

        $this->info('Adding Products...');
        $sitemap->add($this->productCommerceSitemapTags());

        $this->info('Adding Blog Posts...');
        $sitemap->add(BlogPost::query()
            ->with([
                'translations' => fn ($query) => $query->whereIn('locale', $this->supportedLocales),
            ])
            ->published()
            ->get()
        );

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully at public/sitemap.xml');

        return Command::SUCCESS;
    }

    public function __construct(
        private readonly ProductCatalogReader $catalogReader
    ) {
        parent::__construct();
        $this->locales = $this->resolveSitemapLocales();
        $this->supportedLocales = LocaleProfile::supported();
    }

    /**
     * Resolve supported locales for sitemap.
     *
     * @return array<string, string>
     */
    private function resolveSitemapLocales(): array
    {
        return LocaleProfile::alternates();
    }

    private function defaultLocalePrefix(): string
    {
        return LocaleProfile::defaultPrefix();
    }

    /**
     * @return array<int, Url>
     */
    private function catalogItemSitemapTags(): array
    {
        return $this->catalogReader
            ->publicItems(LocaleProfile::default(), withContent: true)
            ->filter(fn (CatalogItem $item): bool => $this->catalogReader->hasPublicDetailPage($item))
            ->filter(fn (CatalogItem $item): bool => $this->shouldIncludeCatalogItem($item))
            ->flatMap(fn (CatalogItem $item): array => $this->catalogItemLocalizedTags($item))
            ->values()
            ->all();
    }

    private function shouldIncludeCatalogItem(CatalogItem $item): bool
    {
        if ($this->catalogReader->primaryGroupCode($item) === 'concept_project') {
            return false;
        }

        if (! $item->hasRenderableDetailContent(LocaleProfile::default())) {
            return false;
        }

        $seo = $this->catalogReader->seoPayload($item);

        return data_get($seo, 'indexable', true) !== false
            && data_get($seo, 'sitemap.include', true) !== false;
    }

    /**
     * @return array<int, Url>
     */
    private function catalogItemLocalizedTags(CatalogItem $item): array
    {
        $seo = $this->catalogReader->seoPayload($item);
        $path = $this->catalogReader->publicPath($item);
        $priority = (float) data_get($seo, 'sitemap.priority', $this->catalogReader->primaryGroupCode($item) === 'application_product' ? 0.8 : 0.7);
        $changeFrequency = (string) data_get($seo, 'sitemap.changefreq', Url::CHANGE_FREQUENCY_WEEKLY);

        $detailLocales = $this->localesFor($item->renderableDetailLocales());
        $tags = $this->localizedTags($path, $item->updated_at, $priority, $changeFrequency, $detailLocales);

        $privacyLocales = $this->localesFor($item->renderablePrivacyLocales());
        if ($privacyLocales !== []) {
            $privacyPath = '/'.trim($path, '/').'/privacy';
            $privacyTags = $this->localizedTags($privacyPath, $item->updated_at, 0.3, Url::CHANGE_FREQUENCY_MONTHLY, $privacyLocales);
            $tags = array_merge($tags, $privacyTags);
        }

        return $tags;
    }

    /**
     * @return array<int, Url>
     */
    private function productCommerceSitemapTags(): array
    {
        return Product::active()
            ->with('catalogItem')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->flatMap(function (Product $product): array {
                $path = $product->sitemapPath();
                if ($path === null) {
                    return [];
                }

                return [
                    ...$this->localizedTags("{$path}/pricing", $product->updated_at, 0.7),
                ];
            })
            ->values()
            ->all();
    }

    private function localizedTags(
        string $path,
        ?\DateTimeInterface $lastModified = null,
        float $priority = 0.8,
        string $changeFrequency = Url::CHANGE_FREQUENCY_WEEKLY,
        ?array $locales = null
    ): array {
        $tags = [];
        $baseUrl = config('app.url');
        $path = '/'.trim($path, '/');
        $locales ??= $this->locales;

        foreach ($locales as $hreflang => $urlPrefix) {
            $localizedUrl = rtrim($baseUrl, '/').($urlPrefix ? '/'.$urlPrefix : '').$path;
            $url = Url::create($localizedUrl)
                ->setLastModificationDate($lastModified ?? now())
                ->setChangeFrequency($changeFrequency)
                ->setPriority($priority);

            foreach ($locales as $altHreflang => $altPrefix) {
                $alternateUrl = rtrim($baseUrl, '/').($altPrefix ? '/'.$altPrefix : '').$path;
                $url->addAlternate($alternateUrl, $altHreflang);
            }

            $defaultPrefix = $this->defaultLocalePrefix();
            $xDefaultPrefix = in_array($defaultPrefix, $locales, true)
                ? $defaultPrefix
                : (reset($locales) ?: $defaultPrefix);
            $xDefaultUrl = rtrim($baseUrl, '/').($xDefaultPrefix ? '/'.$xDefaultPrefix : '').$path;
            $url->addAlternate($xDefaultUrl, 'x-default');
            $tags[] = $url;
        }

        return $tags;
    }

    /**
     * @param  array<int, string>  $locales
     * @return array<string, string>
     */
    private function localesFor(array $locales): array
    {
        $resolved = [];
        foreach ($locales as $locale) {
            $resolved[LocaleProfile::hreflangFor($locale)] = LocaleProfile::prefixFor($locale);
        }

        return $resolved;
    }
}
