<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserSessionToken;
use App\Services\Auth\AuthClientRegistry;
use App\Services\Auth\RefreshTokenPayloadDecoder;
use App\Services\Auth\UserSessionRevoker;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\AccessToken as PassportAccessToken;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\UnencryptedToken;

class ApiAuthController extends Controller
{
    public function __construct(
        protected AuthClientRegistry $authClientRegistry,
        protected RefreshTokenPayloadDecoder $refreshTokenPayloadDecoder,
        protected UserSessionRevoker $userSessionRevoker
    ) {}

    /**
     * Authenticate user via Google ID Token (Native Client Flow) - Proxies to Passport.
     */
    public function google(GoogleAuthRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            'grant_type' => 'social',
            'client_id' => config('passport.password_client.id'),
            'client_secret' => config('passport.password_client.secret'),
            'provider' => 'google',
            'access_token' => $validated['id_token'],
            'source_client' => $validated['client_id'],
            'scope' => implode(' ', $this->authClientRegistry->scopesFor((string) $validated['client_id'])),
        ];

        return $this->proxy('post', '/oauth/token', $data);
    }

    /**
     * Refresh the current Token - Proxies to Passport.
     */
    public function refresh(): JsonResponse
    {
        $validated = request()->validate([
            'refresh_token' => 'required|string',
            'client_id' => ['required', 'string', 'max:100', Rule::in($this->authClientRegistry->clientIds())],
            'product_code' => 'required|string|max:100',
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50',
        ]);

        $refreshToken = $validated['refresh_token'];

        if (! $this->authClientRegistry->matchesProduct((string) $validated['client_id'], (string) $validated['product_code'])) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'product_code' => ['Product code is not allowed for this client.'],
                ],
            ], 422);
        }

        $session = $this->sessionFromRefreshToken($refreshToken, request());

        if ($session instanceof JsonResponse) {
            return $session;
        }

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('passport.password_client.id'),
            'client_secret' => config('passport.password_client.secret'),
            'scope' => implode(' ', $this->authClientRegistry->scopesFor((string) $validated['client_id'])),
        ];

        return $this->proxy('post', '/oauth/token', $data, $session);
    }

    /**
     * Helper to proxy requests to internal OAuth routes.
     *
     * @param  array<string, mixed>  $data
     */
    protected function proxy(string $method, string $route, array $data = [], ?UserSession $existingSession = null): JsonResponse
    {
        $url = config('app.url').$route;

        $pendingRequest = Http::asForm();

        if (app()->isLocal()) {
            $pendingRequest = $pendingRequest->withoutVerifying();
        }

        $response = $pendingRequest->post($url, $data);

        if (! $response->successful()) {
            $oauthError = (string) $response->json('error', '');
            $errorCode = $this->mapOAuthErrorCode($oauthError);

            $payload = [
                'success' => false,
                'error' => $errorCode,
                'message' => 'Authentication failed or token expired.',
            ];

            if (config('app.debug')) {
                $payload['debug'] = $response->json();
            }

            return response()->json($payload, $errorCode === 'REFRESH_TOKEN_EXPIRED' ? 401 : $response->status());
        }

        $tokenData = $response->json();

        $responseData = [
            'success' => true,
            'token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenData['expires_in'],
        ];

        // Attach user info for google/refresh calls
        if (request()->routeIs('api.auth.google') || request()->routeIs('api.auth.refresh')) {
            $user = null;

            if (request()->routeIs('api.auth.google')) {
                $user = $this->resolveUserFromAccessToken($tokenData['access_token']);
            } elseif (request()->routeIs('api.auth.refresh')) {
                $user = $this->resolveUserFromAccessToken($tokenData['access_token']);
            }

            if ($user instanceof User) {
                $tokenId = $this->extractTokenIdFromAccessToken($tokenData['access_token']);
                if (is_string($tokenId) && $tokenId !== '') {
                    $session = $this->upsertUserSession($user, request(), $existingSession);
                    $refreshTokenId = $this->refreshTokenIdForAccessToken($tokenId);
                    $this->recordUserSessionToken($session, $tokenId, $refreshTokenId);
                    $responseData['session'] = [
                        'sid' => $session->sid,
                    ];
                }

                $subscriptions = $this->activeSubscriptionsFor($user);
                $user->setRelation('subscriptions', $subscriptions);

                $responseData['user'] = new UserResource($user);
                $responseData['subscriptions'] = SubscriptionResource::collection($subscriptions);
            }
        }

        return response()->json($responseData);
    }

    /**
     * Invalidate the current Access Token.
     */
    public function logout(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user instanceof User) {
            $session = request()->attributes->get('user_session');

            if ($session instanceof UserSession && (int) $session->user_id === (int) $user->id) {
                $this->revokeUserSession($session, 'logout');
            } else {
                $token = $user->token();

                if ($token instanceof Token || $token instanceof PassportAccessToken) {
                    $token->refreshToken?->revoke();
                    $token->revoke();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get authenticated user info.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $subscriptions = $this->activeSubscriptionsFor($user);
        $user->setRelation('subscriptions', $subscriptions);

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
            'subscriptions' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Get current user's active sessions.
     */
    public function sessions(): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
            ], 401);
        }

        $sessions = $user->sessions()
            ->whereNull('revoked_at')
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UserSession $session) => [
                'id' => $session->id,
                'sid' => $session->sid,
                'client_id' => $session->client_id,
                'product_code' => $session->product_code,
                'device_name' => $session->device_name,
                'platform' => $session->platform,
                'app_version' => $session->app_version,
                'user_agent' => $session->user_agent,
                'ip_address' => $session->ip_address,
                'last_seen_at' => $session->last_seen_at,
                'expires_at' => $session->expires_at,
                'revoked_at' => $session->revoked_at,
                'created_at' => $session->created_at,
            ]);

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Revoke a specific session by token id.
     */
    public function revokeSession(UserSession $session): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
            ], 401);
        }

        if ((int) $session->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'FORBIDDEN',
            ], 403);
        }

        DB::transaction(fn () => $this->revokeUserSession($session, 'user_revoked'));

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully.',
        ]);
    }

    public function revokeSessions(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
            ], 401);
        }

        $validated = $request->validate([
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'integer',
            'product_code' => 'nullable|string|max:100',
            'client_id' => ['nullable', 'string', 'max:100', Rule::in($this->authClientRegistry->clientIds())],
            'include_current' => 'sometimes|boolean',
        ]);

        $query = UserSession::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at');

        if (! empty($validated['session_ids'])) {
            $ids = collect((array) $validated['session_ids'])->map(fn ($id): int => (int) $id)->unique()->values();
            $ownedCount = UserSession::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->count();

            if ($ownedCount !== $ids->count()) {
                return response()->json([
                    'success' => false,
                    'error' => 'FORBIDDEN',
                    'message' => 'One or more sessions do not belong to the current user.',
                ], 403);
            }

            $query->whereIn('id', $ids);
        }

        if (! empty($validated['product_code'])) {
            $query->where('product_code', (string) $validated['product_code']);
        }

        if (! empty($validated['client_id'])) {
            $query->where('client_id', (string) $validated['client_id']);
        }

        $currentSession = $request->attributes->get('user_session');
        if (! ($validated['include_current'] ?? false) && $currentSession instanceof UserSession) {
            $query->whereKeyNot($currentSession->id);
        }

        $sessions = $query->with('tokens')->get();
        $revokedCount = DB::transaction(fn (): int => $this->userSessionRevoker->revokeMany($sessions, 'user_bulk_revoked'));

        return response()->json([
            'success' => true,
            'revoked_count' => $revokedCount,
        ]);
    }

    protected function mapOAuthErrorCode(string $oauthError): string
    {
        return match ($oauthError) {
            'invalid_grant' => 'REFRESH_TOKEN_EXPIRED',
            'invalid_client' => 'CLIENT_ERROR',
            default => 'AUTH_FAILED',
        };
    }

    protected function resolveUserFromAccessToken(string $accessToken): ?User
    {
        try {
            $tokenId = $this->extractTokenIdFromAccessToken($accessToken);
            if (! is_string($tokenId) || $tokenId === '') {
                return null;
            }

            $tokenRecord = Token::find($tokenId);

            return $tokenRecord ? User::find($tokenRecord->user_id) : null;
        } catch (\Exception $e) {
            Log::error('[Auth] User resolution from access token failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function extractTokenIdFromAccessToken(string $accessToken): ?string
    {
        try {
            /** @var Parser $parser */
            $parser = app(Parser::class);
            $parsedToken = $parser->parse($accessToken);

            if (! $parsedToken instanceof UnencryptedToken) {
                return null;
            }

            $tokenId = $parsedToken->claims()->get('jti');

            return is_string($tokenId) ? $tokenId : null;
        } catch (\Exception $e) {
            Log::warning('[Auth] Unable to parse access token jti', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function sessionFromRefreshToken(string $refreshToken, Request $request): UserSession|JsonResponse
    {
        $payload = $this->refreshTokenPayloadDecoder->decode($refreshToken);

        if (! $payload) {
            return $this->refreshRejected('REFRESH_TOKEN_EXPIRED', 'Refresh token is invalid or expired.');
        }

        $sessionToken = UserSessionToken::query()
            ->with('session.user')
            ->where('access_token_id', $payload['access_token_id'])
            ->orWhere('refresh_token_id', $payload['refresh_token_id'])
            ->first();

        $session = $sessionToken?->session;

        if (! $session instanceof UserSession) {
            $this->revokeRefreshTokenId($payload['refresh_token_id']);

            return $this->refreshRejected('SESSION_NOT_FOUND', 'Session not found.');
        }

        if ($session->isRevoked()) {
            $this->revokeRefreshTokenId($payload['refresh_token_id']);

            return $this->refreshRejected('SESSION_REVOKED', 'Session revoked.');
        }

        if (! $this->refreshRequestMatchesSession($session, $request)) {
            $this->revokeRefreshTokenId($payload['refresh_token_id']);

            return $this->refreshRejected('SESSION_MISMATCH', 'Refresh token does not match this client session.');
        }

        $sessionUser = $session->user;
        if ($sessionUser instanceof User && $sessionUser->isBanned()) {
            $this->revokeUserSession($session, 'user_banned');

            return response()->json([
                'success' => false,
                'error' => 'USER_BANNED',
                'message' => 'User is banned.',
            ], 403);
        }

        return $session;
    }

    protected function refreshRejected(string $error, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
        ], 401);
    }

    protected function refreshRequestMatchesSession(UserSession $session, Request $request): bool
    {
        return $session->client_id === (string) $request->input('client_id')
            && $session->product_code === (string) $request->input('product_code')
            && $session->device_id_hash === $this->hashDeviceId((string) $request->input('device_id'));
    }

    /**
     * @return EloquentCollection<int, Subscription>
     */
    protected function activeSubscriptionsFor(User $user): EloquentCollection
    {
        return Subscription::query()
            ->with(Subscription::productCatalogRelations())
            ->where('user_id', $user->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get();
    }

    protected function upsertUserSession(User $user, Request $request, ?UserSession $existingSession = null): UserSession
    {
        $deviceIdHash = $this->hashDeviceId((string) $request->input('device_id'));

        $session = $existingSession ?? UserSession::query()
            ->where('user_id', $user->id)
            ->where('client_id', (string) $request->input('client_id'))
            ->where('product_code', (string) $request->input('product_code'))
            ->where('device_id_hash', $deviceIdHash)
            ->whereNull('revoked_at')
            ->first();

        $session ??= new UserSession([
            'sid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'client_id' => (string) $request->input('client_id'),
            'product_code' => (string) $request->input('product_code'),
            'device_id_hash' => $deviceIdHash,
        ]);

        $session->forceFill([
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'last_seen_at' => now(),
            'expires_at' => now()->add(Passport::refreshTokensExpireIn()),
        ])->save();

        return $session;
    }

    protected function hashDeviceId(string $deviceId): string
    {
        return hash_hmac('sha256', $deviceId, (string) config('app.key'));
    }

    protected function recordUserSessionToken(UserSession $session, string $accessTokenId, ?string $refreshTokenId): void
    {
        UserSessionToken::query()->updateOrCreate(
            ['access_token_id' => $accessTokenId],
            [
                'user_session_id' => $session->id,
                'refresh_token_id' => $refreshTokenId,
            ]
        );
    }

    protected function refreshTokenIdForAccessToken(string $accessTokenId): ?string
    {
        $refreshTokenId = DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessTokenId)
            ->value('id');

        return is_string($refreshTokenId) ? $refreshTokenId : null;
    }

    protected function revokeUserSession(UserSession $session, string $reason): void
    {
        $this->userSessionRevoker->revoke($session, $reason);
    }

    protected function revokeRefreshTokenId(string $refreshTokenId): void
    {
        $this->userSessionRevoker->revokeRefreshTokenId($refreshTokenId);
    }
}
