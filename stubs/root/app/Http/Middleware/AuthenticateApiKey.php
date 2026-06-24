<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyPlain = $request->header('X-Api-Key');

        if (! $apiKeyPlain) {
            return response()->json(['message' => 'API Key missing'], 401);
        }

        // Plaintext comparison (key is stored in plaintext)
        $apiKey = ApiKey::with('user')->where('key', $apiKeyPlain)->first();

        if (! $apiKey || ! $apiKey->isValid()) {
            return response()->json(['message' => 'Invalid or revoked API Key'], 401);
        }

        // log usage
        if ($apiKey->last_used_at === null || $apiKey->last_used_at->diffInMinutes(now()) > 1) {
            $apiKey->update(['last_used_at' => now()]);
        }

        // Authenticate only this request; API key calls must not create a web session.
        Auth::onceUsingId($apiKey->user_id);
        $request->setUserResolver(fn () => $apiKey->user);

        try {
            return $next($request);
        } finally {
            Auth::forgetGuards();
        }
    }
}
