<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

/**
 * @property-read Collection<int, BlogPostTranslation> $translations
 */
class BlogPost extends Model implements Sitemapable
{
    use HasFactory;
    use HasLocalizedAttributes;

    private const FULL_TEXT_COLUMNS = ['title', 'excerpt', 'content'];

    /** @var array<int, string>|null */
    private ?array $renderableLocalesCache = null;

    protected static function booted()
    {
        static::deleting(function ($blogPost) {
            if ($blogPost->thumbnail) {
                Storage::disk('public')->delete($blogPost->thumbnail);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'type',
        'content_scope',
        'status',
        'slug',
        'title', // Stores English / Default title
        'content', // Stores English / Default content
        'excerpt', // Stores English / Default excerpt
        'thumbnail',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<BlogPostTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(BlogPostTranslation::class);
    }

    /** @return HasMany<SiteVisitDailyStat, $this> */
    public function viewDailyStats(): HasMany
    {
        return $this->hasMany(SiteVisitDailyStat::class);
    }

    public static function productContentScope(string $productCode): string
    {
        return 'product:'.$productCode;
    }

    public function contentScopeKind(): ?string
    {
        if (! $this->content_scope || ! str_contains($this->content_scope, ':')) {
            return null;
        }

        return explode(':', $this->content_scope, 2)[0];
    }

    public function contentScopeKey(): ?string
    {
        if (! $this->content_scope || ! str_contains($this->content_scope, ':')) {
            return null;
        }

        return explode(':', $this->content_scope, 2)[1];
    }

    public function productCode(): ?string
    {
        return $this->contentScopeKind() === 'product' ? $this->contentScopeKey() : null;
    }

    public function publicUrl(?string $locale = null): ?string
    {
        $prefix = LocaleProfile::prefixFor($locale);
        if ($this->status !== 'published' || ! $this->published_at?->lte(now())) {
            return null;
        }

        $productCode = $this->productCode();
        if ($this->content_scope && ($this->type !== 'guide' || ! $productCode)) {
            return null;
        }

        $prefixPath = $prefix ? "/{$prefix}" : '';
        $path = $productCode
            ? "{$prefixPath}/{$productCode}/guides/{$this->slug}"
            : "{$prefixPath}/blog/{$this->slug}";

        return rtrim((string) config('app.url'), '/').$path;
    }

    /**
     * Locale IDs where this post has enough content to be an indexable page.
     *
     * @return array<int, string>
     */
    public function renderableLocales(): array
    {
        if ($this->renderableLocalesCache !== null) {
            return $this->renderableLocalesCache;
        }

        $locales = [];
        $defaultLocale = LocaleProfile::default();

        if (filled($this->getAttribute('title')) && filled($this->getAttribute('content'))) {
            $locales[] = $defaultLocale;
        }

        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get(['locale', 'title', 'content']);

        foreach ($translations as $translation) {
            if (! in_array($translation->locale, LocaleProfile::supported(), true)) {
                continue;
            }

            if (filled($translation->title) && filled($translation->content)) {
                $locales[] = $translation->locale;
            }
        }

        return $this->renderableLocalesCache = array_values(array_unique($locales));
    }

    /**
     * @return array<string, string> hreflang => URL prefix
     */
    public function renderableLocalePrefixes(): array
    {
        $supportedPrefixes = LocaleProfile::alternates();
        $locales = [];

        foreach ($this->renderableLocales() as $locale) {
            $hreflang = LocaleProfile::hreflangFor($locale);
            if (array_key_exists($hreflang, $supportedPrefixes)) {
                $locales[$hreflang] = $supportedPrefixes[$hreflang];
            }
        }

        return $locales;
    }

    /**
     * Scope for published blog posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for company blog posts (not tied to any content scope).
     */
    public function scopeCompanyBlog($query)
    {
        return $query->whereNull('content_scope');
    }

    /**
     * Scope for product guides.
     */
    public function scopeForProduct($query, string $productCode)
    {
        return $query->where('content_scope', self::productContentScope($productCode));
    }

    /**
     * Scope for published product guide content.
     */
    public function scopeProductGuides($query, string $productCode)
    {
        return $query->where('content_scope', self::productContentScope($productCode))
            ->where('type', 'guide');
    }

    /**
     * Search scope (Main Table + Translations)
     */
    public function scopeSearch($query, $keyword)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        $like = self::likePattern($keyword);

        return $query->where(function ($q) use ($keyword, $like) {
            $q->whereFullText(self::FULL_TEXT_COLUMNS, $keyword)
                ->orWhere('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('content', 'like', $like)
                ->orWhereHas('translations', function ($tq) use ($keyword) {
                    $tq->whereFullText(self::FULL_TEXT_COLUMNS, $keyword);
                })
                ->orWhereHas('translations', function ($tq) use ($like) {
                    $tq->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('content', 'like', $like);
                });
        });
    }

    private static function likePattern(string $keyword): string
    {
        return '%'.addcslashes($keyword, '\\%_').'%';
    }

    // Localization methods provided by HasLocalizedAttributes trait

    /**
     * Get estimated reading time.
     */
    public function getReadingTime(?string $locale = null): int
    {
        $locale = $locale ?? app()->getLocale();
        $content = strip_tags($this->getTranslation('content', $locale) ?? '');

        $wordCount = str_word_count($content);
        preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $content, $matches);
        $charCount = count($matches[0] ?? []);
        $minutes = ($wordCount / 200) + ($charCount / 300);

        return (int) max(1, round($minutes));
    }

    /**
     * Spatie Sitemap Integration
     */
    public function toSitemapTag(): Url|string|array
    {
        $availableLocales = $this->renderableLocalePrefixes();
        $baseUrl = config('app.url');
        $productCode = $this->productCode();

        if ($this->content_scope && ($this->type !== 'guide' || ! $productCode)) {
            return [];
        }

        if ($availableLocales === []) {
            return [];
        }

        $path = $productCode
            ? "/{$productCode}/guides/{$this->slug}"
            : "/blog/{$this->slug}";
        $tags = [];

        foreach ($availableLocales as $hreflang => $urlPrefix) {
            $localizedUrl = rtrim($baseUrl, '/').($urlPrefix ? '/'.$urlPrefix : '').$path;

            $url = Url::create($localizedUrl)
                ->setLastModificationDate($this->published_at ?? $this->updated_at ?? now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.7);

            // Add hreflang only for real locale variants of this page.
            foreach ($availableLocales as $altHreflang => $altPrefix) {
                $alternateUrl = rtrim($baseUrl, '/').($altPrefix ? '/'.$altPrefix : '').$path;
                $url->addAlternate($alternateUrl, $altHreflang);
            }

            $xDefaultPrefix = $availableLocales[LocaleProfile::defaultHreflang()]
                ?? array_values($availableLocales)[0];
            $xDefaultUrl = rtrim($baseUrl, '/').($xDefaultPrefix ? '/'.$xDefaultPrefix : '').$path;
            $url->addAlternate($xDefaultUrl, 'x-default');

            $tags[] = $url;
        }

        return $tags;
    }
}
