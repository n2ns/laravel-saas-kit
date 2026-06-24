<?php

namespace App\Models\Concerns;

use App\Support\LocaleProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified localization trait for models with translations.
 *
 * Provides a standardized fallback chain:
 * 1. Target locale translation
 * 2. Default locale translation
 * 3. Main table attribute
 *
 * Models using this trait MUST define a `translations()` relationship.
 */
trait HasLocalizedAttributes
{
    /** @var array<string, Model|null> */
    private array $localizedTranslationLookup = [];

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeWithLocalizedTranslations(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        $defaultLocale = LocaleProfile::default();
        $locales = array_values(array_unique(array_filter([$locale, $defaultLocale])));

        return $query->with([
            'translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $locales),
        ]);
    }

    /**
     * Get a localized attribute value with standardized fallback.
     *
     * Fallback chain: target locale → default locale translation → main table
     *
     * @param  string  $attribute  The attribute name to retrieve
     * @param  string|null  $locale  Target locale (defaults to app locale)
     * @return mixed The localized value
     */
    public function getLocalized(string $attribute, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->getLocalizedTranslationModel($locale);

        if ($translation && ! empty($translation->$attribute)) {
            return $translation->$attribute;
        }

        $defaultLocale = LocaleProfile::default();
        if ($locale !== $defaultLocale) {
            $enTranslation = $this->getLocalizedTranslationModel($defaultLocale);
            if ($enTranslation && ! empty($enTranslation->$attribute)) {
                return $enTranslation->$attribute;
            }
        }

        return array_key_exists($attribute, $this->getAttributes())
            ? $this->getAttribute($attribute)
            : null;
    }

    /**
     * Alias for getLocalized() to maintain backwards compatibility.
     *
     * @param  string  $field  The field name to retrieve
     * @param  string|null  $locale  Target locale (defaults to app locale)
     * @return mixed The localized value
     */
    public function getTranslation(string $field, ?string $locale = null): mixed
    {
        return $this->getLocalized($field, $locale);
    }

    /**
     * Set translation value for a field.
     *
     * - For default locale, set the main table attribute.
     * - For non-English locales, update translation records directly.
     *
     * @param  string  $field  The field name to set
     * @param  string  $locale  Target locale
     * @param  mixed  $value  The value to set
     */
    public function setTranslation(string $field, string $locale, mixed $value): void
    {
        if ($locale === LocaleProfile::default()) {
            $this->$field = $value;

            return;
        }

        $translation = $this->translations()->updateOrCreate(
            ['locale' => $locale],
            [$field => $value]
        );

        $this->localizedTranslationLookup[$locale] = $translation;
        if (! $this->relationLoaded('translations')) {
            return;
        }

        $translations = $this->getRelationValue('translations');
        if (! $translations instanceof Collection) {
            return;
        }

        $translations = $translations->reject(function (Model $item) use ($translation): bool {
            return $item->getKey() === $translation->getKey();
        });

        $translations->push($translation);
        $this->setRelation('translations', $translations);
    }

    protected function getLocalizedTranslationModel(?string $locale = null): ?Model
    {
        $locale = $locale ?? app()->getLocale();

        if (! array_key_exists($locale, $this->localizedTranslationLookup)) {
            return $this->loadTranslationIntoLookup($locale);
        }

        return $this->localizedTranslationLookup[$locale] ?? null;
    }

    private function loadTranslationIntoLookup(string $locale): ?Model
    {
        if ($this->relationLoaded('translations')) {
            $translations = $this->getRelation('translations');
            if ($translations instanceof Collection) {
                foreach ($translations as $translation) {
                    $translationLocale = (string) $translation->getAttribute('locale');
                    $this->localizedTranslationLookup[$translationLocale] = $translation;
                }
            }

            if (array_key_exists($locale, $this->localizedTranslationLookup)) {
                return $this->localizedTranslationLookup[$locale];
            }

            $this->localizedTranslationLookup[$locale] = null;

            return null;
        }

        $translation = $this->translations()->where('locale', $locale)->first();
        if (! $translation instanceof Model) {
            $this->localizedTranslationLookup[$locale] = null;

            return null;
        }

        if ($this->relationLoaded('translations')) {
            /** @var Collection<int, Model>|null $translations */
            $translations = $this->getRelation('translations');
            if ($translations instanceof Collection) {
                $translations = $translations->reject(function (Model $item) use ($translation): bool {
                    return $item->getKey() === $translation->getKey();
                });
                $translations->push($translation);
                $this->setRelation('translations', $translations);
            }
        }

        $this->localizedTranslationLookup[$locale] = $translation;

        if ($this->relationLoaded('translations')) {
            foreach ($this->getRelation('translations') as $translation) {
                $translationLocale = (string) $translation->getAttribute('locale');
                $this->localizedTranslationLookup[$translationLocale] = $translation;
            }
        }

        return $this->localizedTranslationLookup[$locale] ?? null;
    }
}
