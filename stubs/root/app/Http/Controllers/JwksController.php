<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JwksController extends Controller
{
    /**
     * Return the specific JWKS (JSON Web Key Set) for this application.
     * This endpoint allows external services to verify Passport tokens.
     *
     * Priority: Environment Variable > Storage File
     * This ensures compatibility with both Passport 11 (file-based) and Passport 13 (env-based).
     */
    public function index(): JsonResponse
    {
        // Try environment variable first (Passport 13 standard)
        $publicKeyContent = config('passport.public_key');

        // Fallback to file system (Passport 11 legacy)
        if (empty($publicKeyContent)) {
            $keyPath = storage_path('oauth-public.key');

            if (! file_exists($keyPath)) {
                Log::critical('[JWKS] Public key unavailable - check PASSPORT_PUBLIC_KEY env or oauth-public.key file');

                return response()->json([
                    'error' => 'Public key unavailable',
                    'hint' => 'Run: php artisan passport:keys',
                ], 503);
            }

            $publicKeyContent = file_get_contents($keyPath);
            Log::info('[JWKS] Using public key from storage file');
        } else {
            Log::info('[JWKS] Using public key from environment variable');
        }

        // Parse the public key
        $key = openssl_pkey_get_public($publicKeyContent);

        if (! $key) {
            Log::error('[JWKS] Invalid public key format', [
                'source' => config('passport.public_key') ? 'env' : 'file',
            ]);

            return response()->json(['error' => 'Invalid public key configuration'], 500);
        }

        $details = openssl_pkey_get_details($key);

        if (! isset($details['rsa'])) {
            Log::error('[JWKS] Key is not RSA format', [
                'key_type' => $details['type'] ?? 'unknown',
            ]);

            return response()->json(['error' => 'Key is not an RSA key'], 500);
        }

        // Convert the standard RSA key to JWK format
        $jwk = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            // Simple kid (Key ID). In complex setups, this should match the signed token's kid header.
            // Passport doesn't usually set a kid in the token header by default,
            // relying on the consumer to try available keys.
            'kid' => (string) str(config('app.name', 'saas-starter'))->slug()->append('-signing-key-1'),
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];

        return response()
            ->json(['keys' => [$jwk]])
            ->setPublic()
            ->setMaxAge(3600)
            ->setSharedMaxAge(3600)
            ->setStaleWhileRevalidate(86400);
    }

    /**
     * Base64URL encode string (RFC 4648).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
