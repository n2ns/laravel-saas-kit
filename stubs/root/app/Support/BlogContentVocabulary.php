<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogContentVocabulary
{
    /**
     * @return array<string, string>
     */
    public static function typeOptions(?string $locale = null): array
    {
        return self::labelOptions(self::types(), $locale);
    }

    /**
     * @return array<int, string>
     */
    public static function typeCodes(): array
    {
        return array_keys(self::types());
    }

    public static function defaultType(): string
    {
        foreach (self::types() as $code => $definition) {
            if (($definition['default'] ?? false) === true) {
                return $code;
            }
        }

        return self::typeCodes()[0] ?? 'article';
    }

    /**
     * @return array<string, string>
     */
    public static function topicOptions(?string $locale = null): array
    {
        return self::labelOptions(self::topics(), $locale);
    }

    /**
     * @return array<int, string>
     */
    public static function topicCodes(): array
    {
        return array_keys(self::topics());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function types(): array
    {
        return self::taxonomy()['types'] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function topics(): array
    {
        return self::taxonomy()['topics'] ?? [];
    }

    /**
     * @return array{types: array<string, array<string, mixed>>, topics: array<string, array<string, mixed>>}
     */
    private static function taxonomy(): array
    {
        $schema = self::metaSchema();
        $vocabulary = is_array($schema['x-content-vocabulary'] ?? null) ? $schema['x-content-vocabulary'] : [];

        return [
            'types' => self::definitionsFrom(
                $vocabulary['types'] ?? null,
                $schema['properties']['type']['enum'] ?? null,
                self::defaultTypes()
            ),
            'topics' => self::definitionsFrom(
                $vocabulary['topics'] ?? null,
                $schema['properties']['topics']['items']['enum'] ?? null,
                self::defaultTopics()
            ),
        ];
    }

    /**
     * @param  mixed  $definitions
     * @param  mixed  $codes
     * @param  array<string, array<string, mixed>>  $fallback
     * @return array<string, array<string, mixed>>
     */
    private static function definitionsFrom(mixed $definitions, mixed $codes, array $fallback): array
    {
        if (is_array($definitions)) {
            $normalized = [];

            foreach ($definitions as $code => $definition) {
                if (! is_string($code) || $code === '') {
                    continue;
                }

                $normalized[$code] = is_array($definition) ? $definition : [];
            }

            if ($normalized !== []) {
                return $normalized;
            }
        }

        if (is_array($codes)) {
            $normalized = [];

            foreach ($codes as $code) {
                if (is_string($code) && $code !== '') {
                    $normalized[$code] = [];
                }
            }

            if ($normalized !== []) {
                return $normalized;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, string>
     */
    private static function labelOptions(array $definitions, ?string $locale): array
    {
        $locale = $locale ?? app()->getLocale();
        $options = [];

        foreach ($definitions as $code => $definition) {
            $labels = is_array($definition['labels'] ?? null) ? $definition['labels'] : [];
            $options[$code] = $labels[$locale]
                ?? $labels[LocaleProfile::default()]
                ?? $labels['en']
                ?? (string) Str::of($code)->replace(['-', '_'], ' ')->headline();
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private static function metaSchema(): array
    {
        $path = database_path('seeders/data/posts/_meta_schema.json');
        if (! File::exists($path)) {
            return [];
        }

        $schema = json_decode(File::get($path), true);

        return is_array($schema) ? $schema : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function defaultTypes(): array
    {
        return [
            'article' => ['labels' => ['en' => 'Article'], 'default' => true],
            'guide' => ['labels' => ['en' => 'Guide']],
            'announcement' => ['labels' => ['en' => 'Announcement']],
            'changelog' => ['labels' => ['en' => 'Changelog']],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function defaultTopics(): array
    {
        return [
            'getting-started' => ['labels' => ['en' => 'Getting started']],
            'product' => ['labels' => ['en' => 'Product']],
            'tutorial' => ['labels' => ['en' => 'Tutorial']],
            'release' => ['labels' => ['en' => 'Release']],
            'company' => ['labels' => ['en' => 'Company']],
        ];
    }
}
