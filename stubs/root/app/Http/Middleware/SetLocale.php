<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\LocaleProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Resolves the URL prefix to a Laravel locale via config('app.supported_locales').
     * This eliminates hardcoded locale lists and keeps routing/middleware in sync.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude Admin Panel and Livewire routes from locale processing
        $adminPath = trim((string) config('app.admin_path', 'admin'), '/');

        if ($request->is($adminPath.'*') || $request->is('livewire*')) {
            return $next($request);
        }

        // Parse current URL segment and resolve via config mapping
        $segment = $request->segment(1);
        $supportedLocales = LocaleProfile::map();

        // [LAYER 2] If URL prefix is a valid locale key and NOT the default locale (English)
        if ($segment !== null && $segment !== 'en' && isset($supportedLocales[$segment])) {
            $locale = $supportedLocales[$segment]; // e.g. 'cn' => 'zh_CN'

            // Set application locale (used by __() and trans())
            app()->setLocale($locale);

            // Update URL defaults with the URL prefix (for route() generation)
            URL::defaults(['locale' => $segment]);

            // Strip locale parameter so controllers don't need to accept it
            $request->route()?->forgetParameter('locale');
        } else {
            // Default locale (English)
            app()->setLocale(LocaleProfile::default());

            // Clear URL defaults for locale to ensure un-prefixed generation
            URL::defaults(['locale' => null]);
        }

        return $next($request);
    }
}
