<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SocialAuthService;
use App\Support\InternalRedirectPath;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;

class WebAuthController extends Controller
{
    /**
     * Create a new WebAuthController instance.
     */
    public function __construct(
        protected SocialAuthService $authService
    ) {}

    /**
     * Show login page.
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(localized_route('dashboard'));
        }

        if ($returnUrl = $this->safeReturnUrl($request)) {
            $request->session()->put('url.intended', $returnUrl);
        }

        return view('auth.web-login');
    }

    /**
     * Redirect to Google OAuth for Web Session.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        if ($returnUrl = $this->safeReturnUrl($request)) {
            $request->session()->put('url.intended', $returnUrl);
        }

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        if (app()->isLocal()) {
            $driver->setHttpClient(new Client(['verify' => false]));
        }

        return $driver
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback and establish Web Session.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');

            if (app()->isLocal()) {
                $driver->setHttpClient(new Client(['verify' => false]));
            }

            $googleUser = $driver->user();

            $user = DB::transaction(function () use ($googleUser) {
                return $this->authService->authenticateUser($googleUser, 'google', 'web');
            });

            // Establish Web Session
            Auth::login($user);

            return redirect()->intended(localized_route('dashboard'))
                ->with('success', __('Welcome back, :name!', ['name' => $user->name]));

        } catch (Exception $e) {
            Log::error('Google Web Login failed', ['error' => $e->getMessage()]);

            return redirect(localized_route('login'))->with('error', __('Login failed.'));
        }
    }

    /**
     * Handle Google One Tap callback.
     * Uses nickbeen/socialiteproviders-google-one-tap package.
     */
    public function handleOneTap(Request $request): RedirectResponse
    {
        try {
            $credential = $request->input('credential');

            if (! $credential) {
                throw new Exception('No credential provided.');
            }

            // [Security] Verify Google CSRF Token
            // Google One Tap sets a cookie 'g_csrf_token' and sends it in the body.
            // We must verify they match to prevent CSRF, as recommended by Google and community.
            $csrfTokenCookie = Cookie::get('g_csrf_token');
            $csrfTokenBody = $request->input('g_csrf_token');

            if (! $csrfTokenCookie || ! $csrfTokenBody || $csrfTokenCookie !== $csrfTokenBody) {
                // Log debug info but don't expose too much
                Log::warning('Google One Tap CSRF Mismatch', [
                    'cookie_present' => ! empty($csrfTokenCookie),
                    'body_present' => ! empty($csrfTokenBody),
                    'match' => $csrfTokenCookie === $csrfTokenBody,
                    'all_cookies' => array_keys($request->cookie()),
                ]);
                throw new Exception('CSRF token mismatch.');
            }

            // Use the Socialite package to verify and decode the JWT credential
            // nickbeen package uses standard Socialite methods:
            // ->driver('google-one-tap')->stateless()->userFromToken($token)
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google-one-tap')
                ->stateless()
                ->userFromToken($credential);

            // Get standard user data supported by the provider
            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();
            $name = $googleUser->getName();
            $avatar = $googleUser->getAvatar();

            // Check for email verification in raw payload if available
            // Note: Socialite providers usually abstract this, but raw data is available
            $rawUser = $googleUser->getRaw();
            $emailVerified = $rawUser['email_verified'] ?? false;

            if (! $googleId || ! $email) {
                Log::error('Google One Tap: Missing user data', [
                    'has_id' => ! empty($googleId),
                    'has_email' => ! empty($email),
                    'raw_user' => $rawUser,
                ]);
                throw new Exception('Invalid Google user data.');
            }

            // Find or create user using the new dedicated method
            $user = $this->authService->createGoogleOneTapUser(
                $googleId,
                $email,
                $name,
                $avatar,
                $emailVerified
            );

            if (! $user) {
                throw new Exception('Failed to create or find user.');
            }

            Auth::login($user);

            return redirect()->back()
                ->with('success', __('Welcome back, :name!', ['name' => $user->name]));

        } catch (Exception $e) {
            Log::error('Google One Tap Login failed', ['error' => $e->getMessage()]);

            return redirect(localized_route('login'))->with('error', __('Login failed.'));
        }
    }

    /**
     * Logout user and invalidate Web Session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString() ?: app()->getLocale();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(localized_route('home', ['locale' => $locale]))->with('success', __('You have been logged out.'));
    }

    /**
     * Resolve a safe same-origin URL to return to after login.
     * Prefers an explicit ?redirect= param, then the referer. Auth pages are
     * excluded to avoid redirect loops. Returns null when nothing is safe.
     */
    private function safeReturnUrl(Request $request): ?string
    {
        $candidate = $request->query('redirect') ?: $request->headers->get('referer');

        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        $path = $candidate;

        if (str_starts_with($candidate, 'http')) {
            $parts = parse_url($candidate);

            if (($parts['host'] ?? null) !== $request->getHost()) {
                return null;
            }

            $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        $path = InternalRedirectPath::sanitize($path, '');
        if ($path === '') {
            return null;
        }

        foreach (['/login', '/auth/', '/logout'] as $blocked) {
            if (str_contains($path, $blocked)) {
                return null;
            }
        }

        return $path;
    }
}
