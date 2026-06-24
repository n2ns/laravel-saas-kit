<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StructuredData
{
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => config('app.company_name', config('app.name')),
            'url' => url('/'),
            'logo' => asset('favicon-512.png'),
            'sameAs' => config('app.same_as', []),
            'description' => __('messages.meta.description'),
        ];
    }

    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'name' => __('messages.meta.site_name'),
            'url' => url('/'),
            'publisher' => [
                '@id' => url('/#organization'),
            ],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];
    }

    public static function blogPosting(BlogPost $post, ?string $url = null): array
    {
        return self::article($post, $url ?? url()->current(), 'BlogPosting');
    }

    public static function techArticle(BlogPost $post, ?string $url = null): array
    {
        return self::article($post, $url ?? url()->current(), 'TechArticle');
    }

    public static function productWebPage(Product $product, ?string $url = null): array
    {
        $locale = app()->getLocale();
        $pageUrl = $url ?? url()->current();
        $name = $product->getLocalized('name', $locale);
        $description = $product->getLocalized('subtitle', $locale);

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $pageUrl.'#webpage',
            'url' => $pageUrl,
            'name' => $name,
            'description' => $description,
            'image' => self::assetUrl($product->image),
            'isPartOf' => [
                '@id' => url('/#website'),
            ],
            'publisher' => [
                '@id' => url('/#organization'),
            ],
            'about' => [
                '@type' => 'Thing',
                'name' => $name,
                'description' => $description,
            ],
            'inLanguage' => str_replace('_', '-', $locale),
        ]);
    }

    public static function catalogItemWebPage(CatalogItem $item, ?string $url = null): array
    {
        $locale = app()->getLocale();
        $pageUrl = $url ?? url()->current();
        $name = $item->getLocalized('name', $locale);
        $description = $item->getLocalized('short_description', $locale);

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $pageUrl.'#webpage',
            'url' => $pageUrl,
            'name' => $name,
            'description' => $description,
            'image' => self::assetUrl($item->profile?->image),
            'isPartOf' => [
                '@id' => url('/#website'),
            ],
            'publisher' => [
                '@id' => url('/#organization'),
            ],
            'about' => [
                '@type' => 'CreativeWork',
                'name' => $name,
                'description' => $description,
            ],
            'inLanguage' => str_replace('_', '-', $locale),
        ]);
    }

    public static function breadcrumbList(array $items): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'] ?? null,
                    'item' => $item['url'] ?? null,
                ])
                ->all(),
        ]);
    }

    private static function article(BlogPost $post, string $url, string $type): array
    {
        $locale = app()->getLocale();
        $title = $post->getTranslation('title', $locale);
        $excerpt = $post->getTranslation('excerpt', $locale);
        $content = $post->getTranslation('content', $locale);

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $url.'#article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'headline' => $title,
            'description' => $excerpt,
            'image' => self::assetUrl($post->thumbnail),
            'articleSection' => $post->type,
            'articleBody' => $content ? Str::of($content)->stripTags()->squish()->toString() : null,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            'author' => [
                '@type' => $post->author ? 'Person' : 'Organization',
                'name' => $post->author->name ?? config('app.company_name', config('app.name')),
            ],
            'publisher' => [
                '@id' => url('/#organization'),
            ],
            'isAccessibleForFree' => true,
            'inLanguage' => str_replace('_', '-', $locale),
        ]);
    }

    private static function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            return url($path);
        }

        if (Str::startsWith($path, 'images/')) {
            return asset($path);
        }

        return url(Storage::disk('public')->url($path));
    }

    private static function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
