<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $code
 * @property string $status
 * @property bool $is_visible
 * @property bool $show_on_homepage
 */
class CatalogItem extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    private const TRANSLATION_TEXT_COLUMNS = [
        'name',
        'short_description',
        'long_description',
        'seo_title',
        'seo_description',
        'card_tag',
        'cta_label',
    ];

    private const TRANSLATION_JSON_SEARCH_COLUMNS = [
        'tags',
        'key_points',
        'seo_payload',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const DEVELOPMENT_COLLECTING_INTEREST = 'collecting_interest';

    public const DEVELOPMENT_IN_DEVELOPMENT = 'in_development';

    public const DEVELOPMENT_SHELVED = 'shelved';

    public const DEFAULT_INTEREST_THRESHOLD = 50;

    protected $fillable = [
        'code',
        'status',
        'sort_order',
        'is_visible',
        'show_on_homepage',
        'homepage_sort_order',
        'interest_threshold',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'show_on_homepage' => 'boolean',
            'homepage_sort_order' => 'integer',
            'interest_threshold' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Product, $this>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    /**
     * @return HasMany<CatalogItemTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogItemTranslation::class);
    }

    /**
     * @return HasOne<CatalogItemProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CatalogItemProfile::class);
    }

    /**
     * @return HasOne<CatalogItemDetail, $this>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(CatalogItemDetail::class);
    }

    /**
     * @return HasManyThrough<CatalogItemDetailTranslation, CatalogItemDetail, $this>
     */
    public function detailTranslations(): HasManyThrough
    {
        return $this->hasManyThrough(
            CatalogItemDetailTranslation::class,
            CatalogItemDetail::class,
            'catalog_item_id',
            'catalog_item_detail_id'
        );
    }

    /**
     * @return HasMany<CatalogItemPrivacyPolicy, $this>
     */
    public function privacyPolicies(): HasMany
    {
        return $this->hasMany(CatalogItemPrivacyPolicy::class);
    }

    /**
     * @return HasMany<CatalogItemTaxonomyTerm, $this>
     */
    public function taxonomyAssignments(): HasMany
    {
        return $this->hasMany(CatalogItemTaxonomyTerm::class);
    }

    /**
     * @return BelongsToMany<CatalogTaxonomyTerm, $this>
     */
    public function taxonomyTerms(): BelongsToMany
    {
        return $this->belongsToMany(CatalogTaxonomyTerm::class, 'catalog_item_taxonomy_terms')
            ->withPivot(['catalog_taxonomy_id', 'source', 'note'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<CatalogInterestSignup, $this>
     */
    public function interestSignups(): HasMany
    {
        return $this->hasMany(CatalogInterestSignup::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeHomepageFeatured(Builder $query): Builder
    {
        return $query
            ->published()
            ->visible()
            ->where('show_on_homepage', true)
            ->orderBy('homepage_sort_order')
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePrimaryGroup(Builder $query, string $groupCode): Builder
    {
        return $query->whereHas('taxonomyTerms', function (Builder $termQuery) use ($groupCode): void {
            $termQuery
                ->where('catalog_taxonomy_terms.code', $groupCode)
                ->whereHas('taxonomy', fn (Builder $taxonomyQuery) => $taxonomyQuery->where('code', 'primary_group'));
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublicConcepts(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        $like = self::likePattern($keyword);

        return $query->where(function (Builder $q) use ($keyword, $like): void {
            $q->where('code', 'like', $like)
                ->orWhereHas('translations', function (Builder $translationQuery) use ($keyword): void {
                    $translationQuery->whereFullText(self::TRANSLATION_TEXT_COLUMNS, $keyword);
                });

            $q->orWhereHas('profile', function (Builder $profileQuery) use ($like): void {
                $profileQuery
                    ->where('product_type', 'like', $like)
                    ->orWhere('segment', 'like', $like)
                    ->orWhere('theme_profile', 'like', $like)
                    ->orWhereRaw('CAST(`facts` AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(`aliases` AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(`links` AS CHAR) LIKE ?', [$like]);
            });

            $q->orWhereHas('translations', function (Builder $translationQuery) use ($like): void {
                $translationQuery
                    ->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('long_description', 'like', $like)
                    ->orWhere('seo_title', 'like', $like)
                    ->orWhere('seo_description', 'like', $like)
                    ->orWhere('card_tag', 'like', $like)
                    ->orWhere('cta_label', 'like', $like);

                foreach (self::TRANSLATION_JSON_SEARCH_COLUMNS as $column) {
                    $translationQuery->orWhereRaw("CAST(`{$column}` AS CHAR) LIKE ?", [$like]);
                }
            });

            $q->orWhereHas('detail.translations', function (Builder $detailTranslationQuery) use ($like): void {
                $detailTranslationQuery
                    ->whereRaw('CAST(`detail_sections` AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(`localized_payload` AS CHAR) LIKE ?', [$like]);
            });
        });
    }

    public function isInDevelopment(): bool
    {
        return $this->resolvedProfile()?->development_status === self::DEVELOPMENT_IN_DEVELOPMENT;
    }

    public function effectiveInterestThreshold(): int
    {
        return $this->interest_threshold ?? self::DEFAULT_INTEREST_THRESHOLD;
    }

    public function maybeAdvanceDevelopmentStatus(): bool
    {
        $profile = $this->resolvedProfile();
        if ($profile?->development_status !== self::DEVELOPMENT_COLLECTING_INTEREST) {
            return false;
        }

        if ($this->interestSignups()->count() < $this->effectiveInterestThreshold()) {
            return false;
        }

        $profile->update(['development_status' => self::DEVELOPMENT_IN_DEVELOPMENT]);
        $this->setRelation('profile', $profile->refresh());

        return true;
    }

    public function localizedTranslation(?string $locale = null): ?CatalogItemTranslation
    {
        $locale = LocaleProfile::normalize($locale);
        $translation = $this->getLocalizedTranslationModel($locale);

        return $translation instanceof CatalogItemTranslation ? $translation : null;
    }

    public function localizedDetailTranslation(?string $locale = null): ?CatalogItemDetailTranslation
    {
        $locale = LocaleProfile::normalize($locale);
        $detail = $this->relationLoaded('detail')
            ? $this->detail
            : $this->detail()->with('translations')->first();

        if (! $detail instanceof CatalogItemDetail) {
            return null;
        }

        if ($detail->relationLoaded('translations')) {
            $translation = $detail->translations->firstWhere('locale', $locale);

            return $translation instanceof CatalogItemDetailTranslation ? $translation : null;
        }

        return $detail->translations()
            ->where('locale', $locale)
            ->first();
    }

    public function privacyPolicyFor(?string $locale = null): ?CatalogItemPrivacyPolicy
    {
        $locale = LocaleProfile::normalize($locale);

        if ($this->relationLoaded('privacyPolicies')) {
            $policy = $this->privacyPolicies->firstWhere('locale', $locale);

            return $policy instanceof CatalogItemPrivacyPolicy ? $policy : null;
        }

        return $this->privacyPolicies()
            ->where('locale', $locale)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    public function renderableDetailLocales(): array
    {
        $locales = collect(LocaleProfile::supported());

        $detail = $this->relationLoaded('detail')
            ? $this->detail
            : $this->detail()->with('translations')->first();

        if ($detail instanceof CatalogItemDetail) {
            $translations = $detail->relationLoaded('translations')
                ? $detail->translations
                : $detail->translations()->get();

            $locales = $locales->merge($translations->pluck('locale'));
        }

        return $locales
            ->unique()
            ->filter(fn (string $locale): bool => $this->hasRenderableDetailContent($locale))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function renderablePrivacyLocales(): array
    {
        $locales = collect(LocaleProfile::supported());
        $policies = $this->relationLoaded('privacyPolicies')
            ? $this->privacyPolicies
            : $this->privacyPolicies()->get();

        $locales = $locales->merge($policies->pluck('locale'));

        return $locales
            ->unique()
            ->filter(fn (string $locale): bool => $this->hasRenderablePrivacyContent($locale))
            ->values()
            ->all();
    }

    public function detailContentLocaleFor(string $locale): string
    {
        $locale = LocaleProfile::normalize($locale);

        if ($this->hasRenderableDetailContent($locale)) {
            return $locale;
        }

        $defaultLocale = LocaleProfile::default();

        return $this->hasRenderableDetailContent($defaultLocale) ? $defaultLocale : $locale;
    }

    public function privacyContentLocaleFor(string $locale): string
    {
        $locale = LocaleProfile::normalize($locale);

        if ($this->hasRenderablePrivacyContent($locale)) {
            return $locale;
        }

        $defaultLocale = LocaleProfile::default();

        return $this->hasRenderablePrivacyContent($defaultLocale) ? $defaultLocale : $locale;
    }

    public function hasRenderableDetailContent(string $locale): bool
    {
        $detailTranslation = $this->localizedDetailTranslation($locale);

        return $detailTranslation instanceof CatalogItemDetailTranslation
            && (filled($detailTranslation->detail_sections) || filled($detailTranslation->localized_payload));
    }

    public function hasRenderablePrivacyContent(string $locale): bool
    {
        $policy = $this->privacyPolicyFor($locale);

        return $policy instanceof CatalogItemPrivacyPolicy && ! empty($policy->sections);
    }

    public function getSlugAttribute(): string
    {
        return $this->code;
    }

    private static function likePattern(string $keyword): string
    {
        return '%'.addcslashes($keyword, '\\%_').'%';
    }

    private function resolvedProfile(): ?CatalogItemProfile
    {
        if ($this->relationLoaded('profile')) {
            $profile = $this->getRelation('profile');

            return $profile instanceof CatalogItemProfile ? $profile : null;
        }

        $profile = $this->profile()->first();
        if ($profile instanceof CatalogItemProfile) {
            $this->setRelation('profile', $profile);
        }

        return $profile;
    }
}
