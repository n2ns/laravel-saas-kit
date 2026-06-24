<?php

namespace App\Services\Auth;

use App\Models\UserSession;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;

class UserSessionRevoker
{
    public function revoke(UserSession $session, string $reason): void
    {
        $session->loadMissing('tokens');

        $accessTokenIds = $session->tokens
            ->pluck('access_token_id')
            ->filter()
            ->values();

        if ($accessTokenIds->isNotEmpty()) {
            Passport::token()->newQuery()
                ->whereIn('id', $accessTokenIds)
                ->update(['revoked' => true]);
        }

        $refreshTokenIds = $session->tokens
            ->pluck('refresh_token_id')
            ->filter()
            ->values();

        if ($refreshTokenIds->isNotEmpty()) {
            Passport::refreshToken()->newQuery()
                ->whereIn('id', $refreshTokenIds)
                ->update(['revoked' => true]);
        }

        $session->forceFill([
            'revoked_at' => $session->revoked_at ?? now(),
            'revoked_reason' => $session->revoked_reason ?? $reason,
        ])->save();
    }

    public function revokeRefreshTokenId(string $refreshTokenId): void
    {
        Passport::refreshToken()->newQuery()
            ->whereKey($refreshTokenId)
            ->update(['revoked' => true]);
    }

    /**
     * @param  Collection<int, UserSession>  $sessions
     */
    public function revokeMany(Collection $sessions, string $reason): int
    {
        $count = 0;

        $sessions
            ->filter(fn (UserSession $session): bool => ! $session->isRevoked())
            ->each(function (UserSession $session) use (&$count, $reason): void {
                $this->revoke($session, $reason);
                $count++;
            });

        return $count;
    }
}
