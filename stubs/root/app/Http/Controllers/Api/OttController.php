<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\InternalRedirectPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OttController extends Controller
{
    /**
     * Create a One-Time Token (OTT) for seamless web login.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'redirect_to' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Generate a random token
        $token = Str::random(64);

        // Cache API key: ott:{token}
        // Value: array with user_id and redirect path
        // TTL: 1 minute (short-lived for security)
        Cache::put(
            "ott:{$token}",
            [
                'user_id' => $user->id,
                'redirect_to' => InternalRedirectPath::sanitize($request->input('redirect_to')),
                'created_at' => now(),
            ],
            now()->addMinute()
        );

        return response()->json([
            'success' => true,
            'token' => $token,
            'expires_in' => 60, // seconds
            'url' => route('ott.login', ['token' => $token]),
        ]);
    }
}
