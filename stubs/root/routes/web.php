<?php

use App\Http\Controllers\Auth\OttController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JwksController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SiteEventController;
use App\Http\Middleware\TrackSiteVisit;
use App\Support\LocaleProfile;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Web Routes - Hybrid Prefix Architecture
|--------------------------------------------------------------------------
|
| English (default locale) is served without prefix (e.g. /about).
| Other supported locales are prefixed (e.g. /es/about, /cn/about).
|
*/

// 1. SEO 301 Redirects: Redirect '/en' and '/en/*' to un-prefixed versions
Route::redirect('/en', '/', 301);
Route::get('/en/{any}', function (Request $request, $any) {
    $query = $request->getQueryString();

    return redirect('/'.$any.($query ? '?'.$query : ''), 301);
})->where('any', '.*');

// Closure defining all business routes
$defineRoutes = function () {
    // Static pages
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [HomeController::class, 'about'])->name('about');
    Route::get('/support', [HomeController::class, 'contact'])->name('support');
    Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
    Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
    Route::get('/refund', [HomeController::class, 'refund'])->name('refund');
    Route::get('/account-access', [HomeController::class, 'accountAccess'])->name('account-access');
    Route::get('/get-started', [HomeController::class, 'getStarted'])->name('get-started');
    Route::get('/account', [HomeController::class, 'account'])->name('account');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Blog
    Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogPostController::class, 'show'])->name('blog.show');

    // Checkout
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/checkout/{product}/{plan?}', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::get('/portal/{product?}', [CheckoutController::class, 'portal'])->name('checkout.portal')->middleware('auth');

    // Auth (guest only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    });

    // Dashboard (authenticated)
    Route::middleware('auth')->prefix('dashboard')->name('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/subscriptions', [DashboardController::class, 'subscriptions'])->name('.subscriptions');
        Route::get('/orders', [DashboardController::class, 'orders'])->name('.orders');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('.settings');
    });

    // Canonical catalog detail routes. Keep these after fixed sections so
    // reserved slugs such as blog, checkout, and dashboard win first.
    Route::get('/{slug}/pricing', [PlanController::class, 'pricing'])->name('catalog.pricing');
    Route::get('/{slug}/privacy', [ProductController::class, 'privacy'])->name('catalog.privacy');
    Route::get('/{slug}', [ProductController::class, 'show'])->name('catalog.show');
};

// 2. Localized Route Group (for non-default languages: es, de, cn)
$prefixedLocales = array_filter(array_keys(LocaleProfile::map()), fn ($k) => $k !== 'en');
$localeRegex = implode('|', $prefixedLocales);

Route::prefix('{locale}')
    ->where(['locale' => $localeRegex])
    ->name('localized.')
    ->group($defineRoutes);

// 3. Default Route Group (for un-prefixed default language)
$defineRoutes();

$adminPath = trim((string) config('app.admin_path', 'admin'), '/');

// Global routes (no locale prefix needed)
Route::get("/{$adminPath}/blog-posts/{blogPost}/preview", [BlogPostController::class, 'preview'])
    ->middleware('auth')
    ->name('admin.blog-posts.preview');

Route::get('/auth/google', [WebAuthController::class, 'redirectToGoogle'])
    ->middleware('throttle:web-auth')
    ->name('auth.google');
Route::get('/auth/google/callback', [WebAuthController::class, 'handleGoogleCallback'])
    ->middleware('throttle:web-auth-callback')
    ->name('auth.google.callback');
Route::post('/auth/google/one-tap', [WebAuthController::class, 'handleOneTap'])
    ->middleware('throttle:web-auth')
    ->name('auth.google.one-tap');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/site-events', [SiteEventController::class, 'store'])->name('site-events.store');

Route::stripeWebhooks('stripe/webhook');

Route::get('/ott/login', [OttController::class, 'login'])
    ->middleware('throttle:ott-login')
    ->name('ott.login');

Route::get('/.well-known/jwks.json', [JwksController::class, 'index'])
    ->withoutMiddleware([
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        TrackSiteVisit::class,
    ])
    ->name('jwks');

Route::fallback(fn () => abort(404));
