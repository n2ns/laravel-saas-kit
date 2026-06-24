<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InternalRedirectPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * OTT (One-Time Token) Web Controller.
 *
 * Handles the web-side of OTT authentication flow.
 * Users are redirected here from extensions/apps to
 * seamlessly log into the web dashboard.
 */
class OttController extends Controller
{
    /**
     * Verify OTT and log in user.
     *
     * GET /ott/login?token=xxx
     *
     * This is a web route that:
     * 1. Validates the one-time token
     * 2. Creates a session for the user
     * 3. Deletes the token (one-time use)
     * 4. Redirects to the intended page
     */
    public function login(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (empty($token)) {
            return redirect(localized_route('login'))
                ->with('error', __('Invalid login link. Please try again.'));
        }

        // Retrieve and delete OTT from cache (atomic operation)
        $cacheKey = "ott:{$token}";
        $ottData = Cache::pull($cacheKey);

        if (! $ottData) {
            Log::warning('[OTT] Invalid or expired token attempted', [
                'token_prefix' => substr($token, 0, 8).'...',
                'ip' => $request->ip(),
            ]);

            return redirect(localized_route('login'))
                ->with('error', __('Login link expired or invalid. Please try again.'));
        }

        // Find and authenticate user
        $user = User::find($ottData['user_id']);

        if (! $user) {
            Log::error('[OTT] User not found for valid token', [
                'user_id' => $ottData['user_id'],
            ]);

            return redirect(localized_route('login'))
                ->with('error', __('User not found. Please contact support.'));
        }

        // Check if user is banned
        if ($user->banned_at) {
            Log::warning('[OTT] Banned user attempted login', [
                'user_id' => $user->id,
            ]);

            return redirect(localized_route('login'))
                ->with('error', __('Your account has been suspended.'));
        }

        // Log the user in
        Auth::login($user);

        Log::info('[OTT] User logged in via OTT', [
            'user_id' => $user->id,
            'redirect_to' => $ottData['redirect_to'] ?? null,
        ]);

        // Redirect to intended page
        $redirectTo = InternalRedirectPath::sanitize($ottData['redirect_to'] ?? null);

        return redirect($redirectTo)
            ->with('success', __('Welcome back, :name!', ['name' => $user->name]));
    }
}
