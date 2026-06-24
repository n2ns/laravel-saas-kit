<?php

namespace App\Auth\Repositories;

use App\Auth\Entities\AccessToken;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessTokenRepository as BaseAccessTokenRepository;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * Custom Access Token Repository for Passport 13.
 *
 * This class returns our custom AccessToken entity which handles
 * JWT claim injection internally.
 */
class AccessTokenRepository extends BaseAccessTokenRepository
{
    /**
     * Create a new access token repository instance.
     */
    public function __construct(Dispatcher $events)
    {
        parent::__construct($events);
    }

    /**
     * Create a new access token instance.
     *
     * Overrides the parent method to return our custom AccessToken entity.
     */
    /**
     * @param  mixed  $userIdentifier
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): AccessTokenEntityInterface {
        /** @var string|null $userIdentifier */
        // Return our custom entity instead of the default Passport one
        return new AccessToken($userIdentifier, $scopes, $clientEntity);
    }
}
