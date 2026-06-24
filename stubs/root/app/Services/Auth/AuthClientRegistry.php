<?php

namespace App\Services\Auth;

class AuthClientRegistry
{
    /**
     * @return array<int, string>
     */
    public function clientIds(): array
    {
        return array_values(array_unique(array_merge(
            ['web'],
            array_keys(config('auth_clients.clients', [])),
        )));
    }

    public function isKnown(string $clientId): bool
    {
        return in_array($clientId, $this->clientIds(), true);
    }

    public function matchesProduct(string $clientId, string $productCode): bool
    {
        $expectedProduct = config("auth_clients.clients.{$clientId}.product_code");

        return ! is_string($expectedProduct) || $expectedProduct === '' || $expectedProduct === $productCode;
    }

    /**
     * @return array<int, string>
     */
    public function scopesFor(string $clientId): array
    {
        $tokenName = config("auth_clients.clients.{$clientId}.token_name")
            ?? config("auth_clients.valid_clients.{$clientId}");

        $scopes = is_string($tokenName)
            ? config("auth_clients.client_scopes.{$tokenName}", ['api'])
            : ['api'];

        return is_array($scopes) ? array_values(array_filter($scopes, 'is_string')) : ['api'];
    }
}
