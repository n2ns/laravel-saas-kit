<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Encryption\Encrypter;
use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptTrait;

class RefreshTokenPayloadDecoder
{
    use CryptTrait;

    public function __construct(Encrypter $encrypter)
    {
        $this->setEncryptionKey(Passport::tokenEncryptionKey($encrypter));
    }

    /**
     * @return array{
     *     client_id: string,
     *     refresh_token_id: string,
     *     access_token_id: string,
     *     scopes: array<int, mixed>,
     *     user_id: int|string|null,
     *     expire_time: int
     * }|null
     */
    public function decode(string $refreshToken): ?array
    {
        try {
            $payload = json_decode($this->decrypt($refreshToken), true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload)
            || ! isset($payload['refresh_token_id'], $payload['access_token_id'], $payload['client_id'])
            || ! is_string($payload['refresh_token_id'])
            || ! is_string($payload['access_token_id'])
            || ! is_string($payload['client_id'])
            || ! isset($payload['scopes'], $payload['user_id'], $payload['expire_time'])
            || ! is_array($payload['scopes'])
            || ! is_numeric($payload['expire_time'])
        ) {
            return null;
        }

        return $payload;
    }
}
