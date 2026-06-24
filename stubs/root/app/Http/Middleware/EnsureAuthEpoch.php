<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce the auth_epoch claim for instant, blacklist-free token revocation.
 *
 * The JWT carries the auth_epoch value captured at issuance; the authenticated
 * user (already loaded by the api guard) carries the current value. A mismatch
 * means the user's epoch was bumped (plan change, ban, forced logout) and every
 * token issued before the bump must be rejected. No extra query: the user is
 * already resolved, the claim is read from the bearer token without re-verifying
 * the signature (the api guard validated it first).
 *
 * Fails open only when there is no bearer token, e.g. Passport::actingAs tests.
 * A real bearer token that lacks auth_epoch is treated as stale because it was
 * minted before epoch-based revocation existed.
 *
 * Runs after `auth:api`.
 */
class EnsureAuthEpoch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $request->bearerToken();
        $tokenEpoch = $user instanceof User && $token ? $this->epochFromToken($token) : null;

        if ($token && ($tokenEpoch === null || $tokenEpoch !== (int) ($user->auth_epoch ?? 1))) {
            return response()->json([
                'success' => false,
                'error' => 'AUTH_EPOCH_STALE',
                'message' => 'Session invalidated. Please sign in again.',
            ], 401);
        }

        return $next($request);
    }

    private function epochFromToken(string $accessToken): ?int
    {
        try {
            $parsed = app(Parser::class)->parse($accessToken);

            if (! $parsed instanceof UnencryptedToken) {
                return null;
            }

            $epoch = $parsed->claims()->get('auth_epoch');

            return is_numeric($epoch) ? (int) $epoch : null;
        } catch (\Exception) {
            return null;
        }
    }
}
