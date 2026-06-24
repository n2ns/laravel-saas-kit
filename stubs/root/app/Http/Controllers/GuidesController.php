<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class GuidesController extends Controller
{
    public function index(string $productCode): View
    {
        $locale = app()->getLocale();
        $product = $this->guideProductQuery($locale)
            ->where('code', $productCode)
            ->firstOrFail();

        $guides = BlogPost::with('author')
            ->published()
            ->productGuides($productCode)
            ->withLocalizedTranslations($locale)
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('guides.index', compact('product', 'guides', 'productCode'));
    }

    public function show(Request $request, string $productCode, string $slug): View
    {
        $locale = app()->getLocale();
        $product = $this->guideProductQuery($locale)
            ->where('code', $productCode)
            ->firstOrFail();

        $guide = BlogPost::with('author')
            ->published()
            ->productGuides($productCode)
            ->where('slug', $slug)
            ->with('translations')
            ->firstOrFail();

        $contentLocale = $this->contentLocaleFor($guide, $locale);

        return $this->renderGuide($product, $guide, $productCode, $contentLocale);
    }

    public function preview(Request $request, BlogPost $blogPost): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $locale = $request->string('locale')->toString();
        if ($locale && in_array($locale, LocaleProfile::supported(), true)) {
            app()->setLocale($locale);
        }

        $locale = app()->getLocale();
        $relations = ['author', 'translations'];

        $blogPost->loadMissing($relations);

        $productCode = $blogPost->productCode() ?? abort(404);
        $product = $this->guideProductQuery($locale)
            ->where('code', $productCode)
            ->firstOrFail();

        return $this->renderGuide($product, $blogPost, $productCode, $locale);
    }

    private function guideProductQuery(string $locale): Builder
    {
        $locales = array_values(array_unique([$locale, LocaleProfile::default()]));

        return Product::query()->with([
            'catalogItem.profile',
            'catalogItem.translations' => fn ($query) => $query->whereIn('locale', $locales),
        ]);
    }

    private function renderGuide(Product $product, BlogPost $guide, string $productCode, ?string $contentLocale = null): View
    {
        $locale = app()->getLocale();
        $contentLocale = $contentLocale ?? $this->contentLocaleFor($guide, $locale);
        request()->attributes->set('seo_content_locale', $contentLocale);

        $htmlContent = app(MarkdownRenderer::class)
            ->toHtml($guide->getTranslation('content', $contentLocale) ?? '');

        $seoContentLocale = $contentLocale;
        $seoCanonicalUrl = null;
        $seoAlternates = null;
        if (! request()->routeIs('admin.*')) {
            $routeParameters = ['productCode' => $productCode, 'slug' => $guide->slug];
            $seoCanonicalUrl = localized_route('catalog.guides.show', $routeParameters + ['locale' => $contentLocale]);
            $seoAlternates = $this->seoAlternates($guide, 'catalog.guides.show', $routeParameters);
        }

        return view('guides.show', compact('product', 'guide', 'htmlContent', 'productCode', 'contentLocale', 'seoContentLocale', 'seoCanonicalUrl', 'seoAlternates'));
    }

    private function contentLocaleFor(BlogPost $guide, string $locale): string
    {
        if ($this->hasRenderableContent($guide, $locale)) {
            return $locale;
        }

        $defaultLocale = LocaleProfile::default();
        if ($locale !== $defaultLocale && $this->hasRenderableContent($guide, $defaultLocale)) {
            return $defaultLocale;
        }

        abort(404);
    }

    private function hasRenderableContent(BlogPost $guide, string $locale): bool
    {
        return in_array($locale, $guide->renderableLocales(), true);
    }

    /**
     * @param  array<string, string>  $routeParameters
     * @return array<string, string>
     */
    private function seoAlternates(BlogPost $guide, string $routeName, array $routeParameters): array
    {
        $alternates = [];
        $locales = $guide->renderableLocales();

        foreach ($locales as $locale) {
            $alternates[LocaleProfile::hreflangFor($locale)] = localized_route(
                $routeName,
                $routeParameters + ['locale' => $locale]
            );
        }

        $xDefaultLocale = in_array(LocaleProfile::default(), $locales, true)
            ? LocaleProfile::default()
            : ($locales[0] ?? null);

        if ($xDefaultLocale !== null) {
            $alternates['x-default'] = localized_route(
                $routeName,
                $routeParameters + ['locale' => $xDefaultLocale]
            );
        }

        return $alternates;
    }
}
