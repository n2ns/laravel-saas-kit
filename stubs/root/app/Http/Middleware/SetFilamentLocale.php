<?php

namespace App\Http\Middleware;

use App\Support\LocaleProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The admin panel always uses the application's default locale.
        App::setLocale(LocaleProfile::default());

        return $next($request);
    }
}
