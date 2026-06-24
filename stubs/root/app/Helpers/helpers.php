<?php

use App\Support\LocaleProfile;

if (! function_exists('localized_route')) {
    /**
     * Generate localized URL dynamically depending on current locale.
     */
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $targetLocale = null;
        if (is_array($parameters) && isset($parameters['locale'])) {
            $targetLocale = $parameters['locale'];
        } else {
            $defaults = app('url')->getDefaultParameters();
            if (isset($defaults['locale'])) {
                $targetLocale = $defaults['locale'];
            } else {
                $targetLocale = app()->getLocale();
            }
        }

        $prefix = LocaleProfile::prefixFor($targetLocale);

        if ($prefix === '') {
            if (is_array($parameters)) {
                unset($parameters['locale']);
            }

            return route($name, $parameters, $absolute);
        }

        if (! is_array($parameters)) {
            return route("localized.{$name}", [$prefix, $parameters], $absolute);
        }

        $parameters['locale'] = $prefix;

        return route("localized.{$name}", $parameters, $absolute);
    }
}
