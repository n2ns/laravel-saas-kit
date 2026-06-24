<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserSessionToken;
use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $next($request);
        }

        $user = $request->user();
        $tokenId = $this->tokenIdFromToken($token);

        if (! $user instanceof User || $tokenId === null) {
            return $this->reject('SESSION_NOT_FOUND', 'Session not found.');
        }

        $sessionToken = UserSessionToken::query()
            ->with('session')
            ->where('access_token_id', $tokenId)
            ->first();

        $session = $sessionToken?->session;

        if (! $session || (int) $session->user_id !== (int) $user->id) {
            return $this->reject('SESSION_NOT_FOUND', 'Session not found.');
        }

        if ($session->isRevoked()) {
            return $this->reject('SESSION_REVOKED', 'Session revoked.');
        }

        if ($user->isBanned()) {
            return response()->json([
                'success' => false,
                'error' => 'USER_BANNED',
                'message' => 'User is banned.',
            ], 403);
        }

        $request->attributes->set('user_session', $session);

        return $next($request);
    }

    private function tokenIdFromToken(string $accessToken): ?string
    {
        try {
            $parsed = app(Parser::class)->parse($accessToken);

            if (! $parsed instanceof UnencryptedToken) {
                return null;
            }

            $tokenId = $parsed->claims()->get('jti');

            return is_string($tokenId) && $tokenId !== '' ? $tokenId : null;
        } catch (\Exception) {
            return null;
        }
    }

    private function reject(string $error, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
        ], 401);
    }
}
