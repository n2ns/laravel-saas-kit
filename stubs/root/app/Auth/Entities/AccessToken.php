<?php

namespace App\Auth\Entities;

use App\Models\User;
use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken as BridgeAccessToken;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * Custom AccessToken entity for injecting custom JWT claims.
 *
 * Compliant with Passport 13.x and League OAuth2 Server.
 */
class AccessToken extends BridgeAccessToken
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;

    /**
     * Generate a JWT from the access token.
     *
     * Overrides AccessTokenTrait::convertToJWT to inject custom claims.
     */
    public function convertToJWT(): Token
    {
        $this->initJwtConfiguration();

        $builder = $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable)
            ->canOnlyBeUsedAfter(new DateTimeImmutable)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) ($this->getUserIdentifier() ?? $this->getClient()->getIdentifier()))
            ->withClaim('scopes', $this->getScopes());

        // Inject custom claims if user identifier is present
        if ($userId = $this->getUserIdentifier()) {
            $user = User::find($userId);
            if ($user) {
                $builder = $builder
                    ->withClaim('plan', $user->getPlan())
                    ->withClaim('status', $user->getStatus())
                    ->withClaim('auth_epoch', (int) ($user->auth_epoch ?? 1))
                    ->withClaim('name', $user->name)
                    ->withClaim('email', $user->email);

                if ($user->avatar) {
                    $builder = $builder->withClaim('avatar', $user->avatar);
                }
            }
        }

        return $builder->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }
}
