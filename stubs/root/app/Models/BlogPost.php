<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Support\BlogContentVocabulary;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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
        'geo_tags',
        'topics',
        'seo_keywords',
        'related_slugs',
        'status',
        'is_pinned',
        'pin_order',
        'pinned_until',
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
            'is_pinned' => 'boolean',
            'pin_order' => 'integer',
            'pinned_until' => 'datetime',
            'geo_tags' => 'array',
            'topics' => 'array',
            'seo_keywords' => 'array',
            'related_slugs' => 'array',
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

    /**
     * Related posts via layered fallback:
     * 1) explicit related_slugs, 2) same geo_tags,
     * 3) same topics, 4) same type.
     *
     * @return \Illuminate\Support\Collection<int, BlogPost>
     */
    public function relatedPosts(int $limit = 4, ?string $locale = null): \Illuminate\Support\Collection
    {
        $locale = $locale ?? app()->getLocale();
        $collected = collect();
        $excludeIds = [$this->id];

        $baseQuery = function () use ($locale) {
            return static::query()
                ->published()
                ->withLocalizedTranslations($locale)
                ->where('id', '!=', $this->id);
        };

        $take = function ($query) use (&$collected, &$excludeIds, $limit): void {
            if ($collected->count() >= $limit) {
                return;
            }

            $posts = $query->whereNotIn('id', $excludeIds)
                ->orderByDesc('published_at')
                ->limit($limit - $collected->count())
                ->get();

            foreach ($posts as $post) {
                $collected->push($post);
                $excludeIds[] = $post->id;
            }
        };

        if (! empty($this->related_slugs)) {
            static::query()
                ->published()
                ->withLocalizedTranslations($locale)
                ->whereIn('slug', $this->related_slugs)
                ->where('id', '!=', $this->id)
                ->get()
                ->sortBy(fn (BlogPost $post): int => array_search($post->slug, $this->related_slugs, true))
                ->each(function (BlogPost $post) use (&$collected, &$excludeIds, $limit): void {
                    if ($collected->count() < $limit) {
                        $collected->push($post);
                        $excludeIds[] = $post->id;
                    }
                });
        }

        if (! empty($this->geo_tags)) {
            $take($this->matchAnyJson($baseQuery(), 'geo_tags', $this->geo_tags));
        }

        if (! empty($this->topics)) {
            $take($this->matchAnyJson($baseQuery(), 'topics', $this->topics));
        }

        $take($baseQuery()->where('type', $this->type));

        return $collected->take($limit)->values();
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(?string $locale = null): array
    {
        return BlogContentVocabulary::typeOptions($locale);
    }

    /**
     * @return array<int, string>
     */
    public static function typeCodes(): array
    {
        return BlogContentVocabulary::typeCodes();
    }

    public static function defaultType(): string
    {
        return BlogContentVocabulary::defaultType();
    }

    public function typeLabel(?string $locale = null): string
    {
        return self::typeLabelFor($this->type, $locale);
    }

    public static function typeLabelFor(string $type, ?string $locale = null): string
    {
        return self::typeOptions($locale)[$type] ?? (string) str($type)->replace(['-', '_'], ' ')->headline();
    }

    public function isPinnedActive(): bool
    {
        return $this->is_pinned && (! $this->pinned_until || $this->pinned_until->isFuture());
    }

    /**
     * @return array<string, string>
     */
    public static function topicOptions(?string $locale = null): array
    {
        return BlogContentVocabulary::topicOptions($locale);
    }

    /**
     * @return array<int, string>
     */
    public static function topicCodes(): array
    {
        return BlogContentVocabulary::topicCodes();
    }

    public function publicUrl(?string $locale = null): ?string
    {
        $prefix = LocaleProfile::prefixFor($locale);
        if ($this->status !== 'published' || ! $this->published_at?->lte(now())) {
            return null;
        }

        $prefixPath = $prefix ? "/{$prefix}" : '';
        $path = "{$prefixPath}/blog/{$this->slug}";

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

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true)
            ->where(function ($q): void {
                $q->whereNull('pinned_until')
                    ->orWhere('pinned_until', '>', now());
            });
    }

    public function scopeApplyListingOrder($query, string $sort = 'latest')
    {
        return $sort === 'oldest'
            ? $query->orderBy('published_at')
            : $query->orderByDesc('published_at');
    }

    public function scopeApplyPinnedListingOrder($query, string $sort = 'latest')
    {
        $query->orderByRaw(
            'case when is_pinned = 1 and (pinned_until is null or pinned_until > ?) then 0 else 1 end',
            [now()]
        )->orderByRaw(
            'case when is_pinned = 1 and (pinned_until is null or pinned_until > ?) then pin_order else 0 end',
            [now()]
        );

        return $sort === 'oldest'
            ? $query->orderBy('published_at')
            : $query->orderByDesc('published_at');
    }

    public function scopeWithTopic($query, ?string $topic)
    {
        if (! $topic || ! in_array($topic, self::topicCodes(), true)) {
            return $query;
        }

        return $query->whereJsonContains('topics', $topic);
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
        $supportsFullText = DB::connection()->getDriverName() !== 'sqlite';

        return $query->where(function ($q) use ($keyword, $like, $supportsFullText) {
            $q->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('content', 'like', $like);

            if ($supportsFullText) {
                $q->orWhereFullText(self::FULL_TEXT_COLUMNS, $keyword)
                    ->orWhereHas('translations', function ($tq) use ($keyword) {
                        $tq->whereFullText(self::FULL_TEXT_COLUMNS, $keyword);
                    });
            }

            $q->orWhereHas('translations', function ($tq) use ($like) {
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

    /**
     * @param  array<int, string>  $values
     */
    private function matchAnyJson($query, string $column, array $values)
    {
        return $query->where(function ($inner) use ($column, $values): void {
            foreach ($values as $value) {
                $inner->orWhereJsonContains($column, $value);
            }
        });
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
        if ($availableLocales === []) {
            return [];
        }

        $path = "/blog/{$this->slug}";
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
