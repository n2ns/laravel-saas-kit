<?php

namespace App\Support;

final class InternalRedirectPath
{
    public static function default(): string
    {
        return localized_route('dashboard', ['locale' => LocaleProfile::default()], false);
    }

    public static function sanitize(mixed $path, ?string $default = null): string
    {
        $default ??= self::default();

        if (! is_string($path)) {
            return $default;
        }

        if (preg_match('/\p{Cc}/u', $path) !== 0) {
            return $default;
        }

        $path = trim($path);
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $default;
        }

        return $path;
    }
}
