<?php

namespace App\Support;

final class LocaleProfile
{
    /**
     * @return array<string, string> URL prefix => internal locale
     */
    public static function map(): array
    {
        return config('app.supported_locales', [self::fallbackLocaleFallback() => self::fallbackLocaleFallback()]);
    }

    /**
     * Supported locales as locale IDs.
     *
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return array_values(self::map());
    }

    /**
     * Default/fallback locale.
     */
    public static function default(): string
    {
        return self::fallbackLocale();
    }

    public static function fallbackLocale(): string
    {
        $map = self::map();

        return config('app.fallback_locale', array_key_first($map) ?: 'en');
    }

    private static function fallbackLocaleFallback(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * URL prefixes for default locale.
     */
    public static function defaultPrefix(): string
    {
        return '';
    }

    public static function prefixFor(?string $locale): string
    {
        if ($locale !== null && self::hasPrefix($locale)) {
            return $locale === 'en' ? '' : $locale;
        }

        $locale = self::normalize($locale);

        if ($locale === self::default()) {
            return '';
        }

        $prefix = array_search($locale, self::map(), true);

        return $prefix === 'en' ? '' : ($prefix ?: '');
    }

    public static function hreflangFor(?string $locale): string
    {
        $prefix = self::prefixFor($locale);
        $hreflang = array_search($prefix, self::alternates(), true);

        return is_string($hreflang) ? $hreflang : self::defaultHreflang();
    }

    public static function defaultHreflang(): string
    {
        return self::hreflangFromLocale(self::default());
    }

    /**
     * Alternate-language tags map with URL prefixes.
     *
     * @return array<string, string> hreflang => URL prefix
     */
    public static function alternates(): array
    {
        $pairs = [];
        foreach (self::map() as $prefix => $locale) {
            $prefixValue = ($prefix === 'en') ? '' : $prefix;
            $pairs[self::hreflangFromLocale($locale)] = $prefixValue;
        }

        return $pairs;
    }

    public static function normalize(?string $locale): string
    {
        return in_array($locale, self::supported(), true) ? $locale : self::default();
    }

    public static function hasPrefix(string $locale): bool
    {
        return array_key_exists($locale, self::map());
    }

    public static function stripPrefixFromPath(string $path): string
    {
        $path = trim($path, '/');

        if ($path === '') {
            return '/';
        }

        [$firstSegment, $remainingPath] = array_pad(explode('/', $path, 2), 2, '');

        if (! self::hasPrefix($firstSegment)) {
            return $path;
        }

        return $remainingPath === '' ? '/' : $remainingPath;
    }

    private static function hreflangFromLocale(string $locale): string
    {
        return $locale === 'zh_CN' ? 'zh-Hans' : $locale;
    }
}
