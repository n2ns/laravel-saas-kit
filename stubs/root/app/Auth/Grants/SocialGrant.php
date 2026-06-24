<?php

namespace App\Auth\Grants;

use App\Services\Auth\SocialAuthService;
use DateInterval;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Bridge\User as BridgeUser;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class SocialGrant extends AbstractGrant
{
    protected SocialAuthService $authService;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        SocialAuthService $authService
    ) {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->authService = $authService;
        $this->refreshTokenTTL = new DateInterval('P3M'); // Default fallback
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'social';
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject tokens into response type
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * Validate the user via Google ID Token.
     *
     * @return UserEntityInterface
     *
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $provider = $this->getRequestParameter('provider', $request);
        $token = $this->getRequestParameter('access_token', $request); // We use 'access_token' param to carry the ID Token
        $sourceClient = $this->getRequestParameter('source_client', $request);

        if (! $provider || ! $token) {
            throw OAuthServerException::invalidRequest('provider', 'Missing provider or token param');
        }

        if ($provider !== 'google') {
            throw OAuthServerException::invalidRequest('provider', 'Unsupported provider');
        }

        // Verify ID Token
        $user = $this->verifyGoogleToken($token, is_string($sourceClient) ? $sourceClient : null);

        if (! $user instanceof UserEntityInterface) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * Verify Google ID Token and retrieve local User.
     *
     * @return UserEntityInterface|null
     */
    protected function verifyGoogleToken(string $idToken, ?string $sourceClient = null)
    {
        try {
            // Use the centralized SocialAuthService to finding OR creating the user.
            // This ensures registration works seamlessly.
            $user = $this->authService->authenticateGoogle($idToken, 'web-oauth-grant', $sourceClient);

            if (! $user) {
                Log::warning('SocialGrant: Authentication failed for token');

                return null;
            }

            // Check if banned etc.
            if ($user->isBanned()) {
                Log::warning("SocialGrant: User {$user->email} is banned");

                return null;
            }

            return new BridgeUser($user->getAuthIdentifier());

        } catch (Exception $e) {
            Log::error('SocialGrant Error: '.$e->getMessage());

            return null;
        }
    }
}
