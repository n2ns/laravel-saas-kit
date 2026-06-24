<?php

namespace App\Models;

use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Product extends Model implements Sitemapable
{
    use HasFactory;

    public const STARTER = 'starter';

    public const SELLABLE_CODES = [
        self::STARTER,
    ];

    public const PAUSE_MAINTENANCE = 'maintenance';

    public const PAUSE_PAYMENT_UPGRADE = 'payment_upgrade';

    public const PAUSE_COMING_SOON = 'coming_soon';

    public const PAUSE_REGION_RESTRICTED = 'region_restricted';

    protected $table = 'products';

    protected $fillable = [
        'catalog_item_id',
        'code',
        'is_active',
        'pause_reason',
        'sort_order',
        'stripe_product_id',
        'pricing_page_url',
        'mcp_server_url',
        'mcp_api_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * @return HasMany<CatalogItemTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogItemTranslation::class, 'catalog_item_id', 'catalog_item_id');
    }

    public function getTranslation(?string $locale = null): ?CatalogItemTranslation
    {
        $locale = LocaleProfile::normalize($locale);

        return $this->resolvedCatalogItem()?->localizedTranslation($locale);
    }

    public function getLocalized(string $field, ?string $locale = null): mixed
    {
        $mappedField = match ($field) {
            'subtitle' => 'short_description',
            'product_tags' => 'tags',
            default => $field,
        };

        return $this->resolvedCatalogItem()?->getLocalized($mappedField, $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSectionsContent(?string $locale = null): array
    {
        $catalogItem = $this->resolvedCatalogItem();
        $translation = $catalogItem?->localizedDetailTranslation($locale)
            ?? $catalogItem?->localizedDetailTranslation(LocaleProfile::default());

        return $translation?->detail_sections ?? [];
    }

    /**
     * @return array<int, mixed>
     */
    public function getLocalizedTags(?string $locale = null): array
    {
        return $this->getLocalized('product_tags', $locale) ?? [];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithLocalizedTranslations(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        $defaultLocale = LocaleProfile::default();
        $locales = array_values(array_unique(array_filter([$locale, $defaultLocale])));

        return $query->with([
            'catalogItem.profile',
            'catalogItem.detail.translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $locales),
            'catalogItem.translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $locales),
        ]);
    }

    /**
     * @return array{hero: array<string, mixed>, sections: array<int, mixed>}
     */
    public function getLocalizedSections(?string $locale = null): array
    {
        $locale = LocaleProfile::normalize($locale);
        $catalogItem = $this->resolvedCatalogItem();
        if (! $catalogItem) {
            return ['hero' => [], 'sections' => []];
        }

        $detailPayload = is_array($catalogItem->detail?->structure_payload) ? $catalogItem->detail->structure_payload : [];
        $sectionBlueprint = is_array($detailPayload['section_blueprint'] ?? null) ? $detailPayload['section_blueprint'] : [];
        $profile = $catalogItem->profile;

        return [
            'hero' => array_merge(is_array($sectionBlueprint['hero'] ?? null) ? $sectionBlueprint['hero'] : [], [
                'tag' => $catalogItem->getLocalized('card_tag', $locale),
                'title' => $catalogItem->getLocalized('name', $locale),
                'subtitle' => $catalogItem->getLocalized('short_description', $locale),
                'product_type' => $profile?->product_type,
                'version' => $profile?->version,
                'release_status' => $profile?->release_status,
                'product_tags' => $catalogItem->getLocalized('tags', $locale) ?? [],
                'metadata' => data_get($profile?->facts, 'metadata', []),
            ]),
            'sections' => is_array($detailPayload['sections'] ?? null) ? $detailPayload['sections'] : [],
        ];
    }

    public function hasPaidPlans(): bool
    {
        if (array_key_exists('has_paid_plans', $this->getAttributes())) {
            return (bool) $this->getAttribute('has_paid_plans');
        }

        if ($this->relationLoaded('plans')) {
            return $this->plans->contains(fn (Plan $plan): bool => $plan->tier !== Plan::TIER_FREE);
        }

        return $this->plans()->where('tier', '!=', Plan::TIER_FREE)->exists();
    }

    public function hasFreePlans(): bool
    {
        if ($this->relationLoaded('plans')) {
            return $this->plans->contains(fn (Plan $plan): bool => $plan->tier === Plan::TIER_FREE);
        }

        return $this->plans()->where('tier', Plan::TIER_FREE)->exists();
    }

    public function hasTopLevelRoute(): bool
    {
        return true;
    }

    public function publicUrl(?string $locale = null, bool $absolute = true): string
    {
        $parameters = ['slug' => $this->code];
        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        return localized_route('catalog.show', $parameters, $absolute);
    }

    public function pricingUrl(?string $locale = null, bool $absolute = true): string
    {
        $parameters = ['slug' => $this->code];
        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        return localized_route('catalog.pricing', $parameters, $absolute);
    }

    public function guidesUrl(?string $locale = null, bool $absolute = true): ?string
    {
        $parameters = ['productCode' => $this->code];
        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        return localized_route('catalog.guides.index', $parameters, $absolute);
    }

    public function isSitemapIndexable(): bool
    {
        $catalogItem = $this->resolvedCatalogItem();

        if (
            ! $this->is_active
            || ! $catalogItem
            || $catalogItem->status !== CatalogItem::STATUS_PUBLISHED
            || ! $catalogItem->is_visible
        ) {
            return false;
        }

        if (! $catalogItem->hasRenderableDetailContent(LocaleProfile::default())) {
            return false;
        }

        $seo = is_array($catalogItem->profile?->seo_payload) ? $catalogItem->profile->seo_payload : [];

        return data_get($seo, 'indexable', true) !== false
            && data_get($seo, 'sitemap.include', true) !== false;
    }

    public function sitemapPath(): ?string
    {
        if (! $this->isSitemapIndexable()) {
            return null;
        }

        return '/'.trim($this->resolvedCatalogItem()?->code ?? $this->code, '/');
    }

    public function isPaused(): bool
    {
        return $this->pause_reason !== null;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->first();
    }

    public function getNameAttribute(): ?string
    {
        return $this->catalogValue('name');
    }

    public function getSubtitleAttribute(): ?string
    {
        return $this->catalogValue('short_description');
    }

    public function getCardTagAttribute(): ?string
    {
        return $this->catalogValue('card_tag');
    }

    public function getCtaLabelAttribute(): ?string
    {
        return $this->catalogValue('cta_label');
    }

    public function getProductTypeAttribute(): ?string
    {
        return $this->catalogValue('product_type');
    }

    public function getVersionAttribute(): ?string
    {
        return $this->catalogValue('version');
    }

    public function getReleaseStatusAttribute(): ?string
    {
        return $this->catalogValue('release_status');
    }

    public function getImageAttribute(): ?string
    {
        return $this->catalogValue('image');
    }

    public function getIconAttribute(): ?string
    {
        return $this->catalogValue('icon');
    }

    public function getThumbnailAttribute(): ?string
    {
        return $this->catalogValue('thumbnail');
    }

    public function getSectionsAttribute(): array
    {
        return $this->catalogDetailPayload()['section_blueprint'] ?? [];
    }

    public function getContentPayloadAttribute(): array
    {
        return $this->catalogDetailPayload()['content_payload'] ?? [];
    }

    public function toSitemapTag(): Url|string|array
    {
        $detailPath = $this->sitemapPath();
        if ($detailPath === null) {
            return [];
        }

        $locales = LocaleProfile::alternates();
        $defaultPrefix = LocaleProfile::defaultPrefix();
        $baseUrl = config('app.url');
        $tags = [];
        $pricingPath = "{$detailPath}/pricing";
        $educationPath = "{$detailPath}/guides";

        foreach ($locales as $hreflang => $urlPrefix) {
            $tags[] = $this->createSitemapUrl($baseUrl, $locales, $detailPath, $urlPrefix, $defaultPrefix, 0.8);
            $tags[] = $this->createSitemapUrl($baseUrl, $locales, $pricingPath, $urlPrefix, $defaultPrefix, 0.7);
            $tags[] = $this->createSitemapUrl($baseUrl, $locales, $educationPath, $urlPrefix, $defaultPrefix, 0.8);
        }

        return $tags;
    }

    /**
     * @param  array<string, string>  $locales
     */
    private function createSitemapUrl(string $baseUrl, array $locales, string $path, string $currentPrefix, string $defaultPrefix, float $priority): Url
    {
        $localizedUrl = rtrim($baseUrl, '/').($currentPrefix ? '/'.$currentPrefix : '').$path;

        $url = Url::create($localizedUrl)
            ->setLastModificationDate($this->updated_at ?? now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority($priority);

        foreach ($locales as $altHreflang => $altPrefix) {
            $alternateUrl = rtrim($baseUrl, '/').($altPrefix ? '/'.$altPrefix : '').$path;
            $url->addAlternate($alternateUrl, $altHreflang);
        }

        $xDefaultUrl = rtrim($baseUrl, '/').($defaultPrefix ? '/'.$defaultPrefix : '').$path;
        $url->addAlternate($xDefaultUrl, 'x-default');

        return $url;
    }

    private function resolvedCatalogItem(): ?CatalogItem
    {
        if ($this->relationLoaded('catalogItem')) {
            $catalogItem = $this->getRelation('catalogItem');

            if ($catalogItem instanceof CatalogItem) {
                $catalogItem->loadMissing(['profile', 'detail.translations']);

                return $catalogItem;
            }

            return null;
        }

        if (! $this->catalog_item_id) {
            return null;
        }

        $catalogItem = $this->catalogItem()->first();
        if ($catalogItem instanceof CatalogItem) {
            $catalogItem->loadMissing(['profile', 'detail.translations']);
            $this->setRelation('catalogItem', $catalogItem);
        }

        return $catalogItem;
    }

    private function catalogValue(string $field): mixed
    {
        $catalogItem = $this->resolvedCatalogItem();

        if (! $catalogItem) {
            return null;
        }

        if (in_array($field, ['product_type', 'version', 'release_status', 'image', 'icon', 'thumbnail'], true)) {
            return $catalogItem->profile?->getAttribute($field);
        }

        return $catalogItem->getLocalized($field, app()->getLocale());
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogDetailPayload(): array
    {
        $payload = $this->resolvedCatalogItem()?->detail?->structure_payload;

        return is_array($payload) ? $payload : [];
    }
}
