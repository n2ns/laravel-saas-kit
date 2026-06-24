<?php

namespace App\Providers;

use App\Auth\Entities\AccessToken;
use App\Auth\Grants\SocialGrant;
use App\Auth\Repositories\AccessTokenRepository;
use App\Models\Subscription;
use App\Services\Auth\SocialAuthService;
use App\Services\ProductService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Bridge\AccessTokenRepository as BaseAccessTokenRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token\Parser as ConcreteParser;
use League\OAuth2\Server\AuthorizationServer;
use SocialiteProviders\GoogleOneTap\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom AccessToken entity for Passport 13 JWT claims
        Passport::useAccessTokenEntity(AccessToken::class);

        // Register custom AccessTokenRepository
        $this->app->singleton(BaseAccessTokenRepository::class, AccessTokenRepository::class);

        // Bind the Lcobucci JWT Parser interface to the concrete implementation.
        // Required by ApiAuthController and EnsureAuthEpoch; tests can still
        // override this binding via $this->mock(Parser::class, ...).
        $this->app->bind(Parser::class, fn () => new ConcreteParser(new JoseEncoder));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // System Strictness & Models
        Model::shouldBeStrict(! $this->app->isProduction());
        Cashier::useSubscriptionModel(Subscription::class);
        // View Composer: Share landing navigation context with the header.
        View::composer('partials.header', function ($view) {
            $route = request()->route();
            $navProducts = app(ProductService::class)->getHomepageProducts();
            $navSingleProduct = count($navProducts) === 1 ? $navProducts[0] : null;

            $view->with([
                'currentRouteName' => $route?->getName() ?? 'home',
                'currentRouteParams' => $route?->parameters() ?? [],
                'navProducts' => $navProducts,
                'navSingleProduct' => $navSingleProduct,
                'navIsSingleProductLanding' => $navSingleProduct !== null,
            ]);
        });

        // Register Google One Tap Socialite Provider (nickbeen)
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('google-one-tap', Provider::class);
        });

        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(20)->by((string) $request->ip()));
        RateLimiter::for('api-refresh', fn (Request $request) => Limit::perMinute(60)->by((string) $request->ip()));
        RateLimiter::for('web-auth', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
        RateLimiter::for('web-auth-callback', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
        RateLimiter::for('ott-login', fn (Request $request) => Limit::perMinute(20)->by((string) $request->ip()));
        RateLimiter::for('product-usage-track', fn (Request $request) => Limit::perMinute(60)->by((string) $request->ip()));
        RateLimiter::for('product-usage-read', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));

        // Passport Configuration - Set TTL first (before server resolution)
        Passport::tokensCan([
            'api' => 'Access product API endpoints',
        ]);
        Passport::tokensExpireIn(now()->addHours(6));
        Passport::refreshTokensExpireIn(now()->addMonths(3));
        Passport::$revokeRefreshTokenAfterUse = false;

        // Register SocialGrant after AuthorizationServer is resolved (Laravel 12 / Passport 13.x pattern)
        $this->app->afterResolving(AuthorizationServer::class, function (AuthorizationServer $server): void {
            $grant = new SocialGrant(
                $this->app->make(RefreshTokenRepository::class),
                $this->app->make(SocialAuthService::class)
            );
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

            $server->enableGrantType($grant, Passport::tokensExpireIn());
        });
    }
}
