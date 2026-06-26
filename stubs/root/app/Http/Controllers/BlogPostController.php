<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\LocaleProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class BlogPostController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $searchQuery = trim($request->string('q')->toString());
        if (mb_strlen($searchQuery) > 120) {
            $searchQuery = mb_substr($searchQuery, 0, 120);
        }

        $sort = $request->string('sort')->toString() === 'oldest' ? 'oldest' : 'latest';
        $type = $request->string('type')->toString();
        if (! in_array($type, BlogPost::typeCodes(), true)) {
            $type = null;
        }

        $topic = $request->string('topic')->toString();
        if (! in_array($topic, BlogPost::topicCodes(), true)) {
            $topic = null;
        }

        $filterQuery = array_filter([
            'q' => $searchQuery !== '' ? $searchQuery : null,
            'sort' => $sort === 'oldest' ? $sort : null,
            'type' => $type,
            'topic' => $topic,
        ], fn ($value): bool => filled($value));
        $searchPreservedQuery = collect($filterQuery)->except('q')->all();

        $blogPosts = BlogPost::published()
            ->withLocalizedTranslations($locale)
            ->when($searchQuery !== '', fn ($query) => $query->search($searchQuery))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->withTopic($topic)
            ->applyListingOrder($sort)
            ->paginate(10)
            ->appends($filterQuery);

        $sortOptions = [
            'latest' => __('messages.blog.sort_latest'),
            'oldest' => __('messages.blog.sort_oldest'),
        ];
        $typeOptions = BlogPost::typeOptions($locale);
        $topicOptions = BlogPost::topicOptions($locale);

        return view('blog.index', compact(
            'blogPosts',
            'searchQuery',
            'sort',
            'sortOptions',
            'type',
            'typeOptions',
            'topic',
            'topicOptions',
            'searchPreservedQuery'
        ));
    }

    public function show(Request $request, string $slug): View
    {
        $blogPost = BlogPost::with('author')
            ->published()
            ->where('slug', $slug)
            ->with('translations')
            ->firstOrFail();

        $request->attributes->set('site_analytics_blog_post_id', $blogPost->id);

        return $this->renderBlogPost($blogPost);
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

        return $this->renderBlogPost($blogPost);
    }

    private function renderBlogPost(BlogPost $blogPost): View
    {
        $locale = app()->getLocale();
        $contentLocale = $this->contentLocaleFor($blogPost, $locale);
        request()->attributes->set('seo_content_locale', $contentLocale);

        $htmlContent = app(MarkdownRenderer::class)
            ->toHtml($blogPost->getTranslation('content', $contentLocale) ?? '');

        $seoContentLocale = $contentLocale;
        $seoCanonicalUrl = null;
        $seoAlternates = null;
        if (! request()->routeIs('admin.*')) {
            $routeParameters = ['slug' => $blogPost->slug];
            $seoCanonicalUrl = localized_route('blog.show', $routeParameters + ['locale' => $contentLocale]);
            $seoAlternates = $this->seoAlternates($blogPost, 'blog.show', $routeParameters);
        }

        return view('blog.show', compact('blogPost', 'htmlContent', 'contentLocale', 'seoContentLocale', 'seoCanonicalUrl', 'seoAlternates'));
    }

    private function contentLocaleFor(BlogPost $blogPost, string $locale): string
    {
        if ($this->hasRenderableContent($blogPost, $locale)) {
            return $locale;
        }

        $defaultLocale = LocaleProfile::default();
        if ($locale !== $defaultLocale && $this->hasRenderableContent($blogPost, $defaultLocale)) {
            return $defaultLocale;
        }

        abort(404);
    }

    private function hasRenderableContent(BlogPost $blogPost, string $locale): bool
    {
        return in_array($locale, $blogPost->renderableLocales(), true);
    }

    /**
     * @param  array<string, string>  $routeParameters
     * @return array<string, string>
     */
    private function seoAlternates(BlogPost $blogPost, string $routeName, array $routeParameters): array
    {
        $alternates = [];
        $locales = $blogPost->renderableLocales();

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
