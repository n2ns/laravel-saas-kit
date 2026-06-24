<?php

namespace App\Services\Auth;

use App\Models\OAuthAccount;
use App\Models\User;
use Exception;
use Google_Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialAuthService
{
    /**
     * Handle Google ID Token authentication.
     */
    public function authenticateGoogle(string $idToken, string $registrationSource = 'web', ?string $firstClient = null): ?User
    {
        $payload = $this->verifyGoogleIdToken($idToken);

        if (! $payload) {
            return null;
        }

        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? '';
        $avatar = $payload['picture'] ?? null;
        $emailVerified = $payload['email_verified'] ?? false;

        return DB::transaction(function () use ($googleId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient) {
            return $this->findOrCreateUser($googleId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient);
        });
    }

    /**
     * Authenticate a user via Socialite User object.
     * Useful for Extension/Web OAuth flows where we have the Socialite User object directly.
     *
     * @param  \Laravel\Socialite\Contracts\User|\Laravel\Socialite\Two\User  $socialUser
     */
    public function authenticateUser($socialUser, string $provider = 'google', string $registrationSource = 'web', ?string $firstClient = null): User
    {
        $socialId = $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName() ?? '';
        $avatar = $socialUser->getAvatar();
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];
        $emailVerified = (bool) ($raw['email_verified'] ?? false);

        return DB::transaction(function () use ($socialId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient) {
            return $this->findOrCreateUser($socialId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient);
        });
    }

    /**
     * Create or find user from Google One Tap data (already verified by Socialite package).
     * This bypasses JWT verification since the package already did that.
     */
    public function createGoogleOneTapUser(
        string $googleId,
        string $email,
        string $name,
        ?string $avatar,
        bool $emailVerified,
        string $registrationSource = 'web-onetap',
        ?string $firstClient = null
    ): User {
        return DB::transaction(function () use ($googleId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient) {
            return $this->findOrCreateUser($googleId, $email, $name, $avatar, $emailVerified, $registrationSource, $firstClient);
        });
    }

    /**
     * Sync a Google user profile already verified by a trusted server-side caller.
     */
    public function syncTrustedGoogleUser(
        string $googleId,
        string $email,
        string $name,
        ?string $avatar,
        string $firstClient,
        string $registrationSource = 'edge-sync'
    ): User {
        return DB::transaction(function () use ($googleId, $email, $name, $avatar, $firstClient, $registrationSource) {
            return $this->findOrCreateUser($googleId, $email, $name, $avatar, true, $registrationSource, $firstClient);
        });
    }

    /**
     * Verify Google ID Token.
     */
    protected function verifyGoogleIdToken(string $idToken): ?array
    {
        try {
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);

            return $client->verifyIdToken($idToken) ?: null;
        } catch (Exception $e) {
            Log::error('Google Token Verification Failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find existing user or create a new one.
     */
    protected function findOrCreateUser(
        string $googleId,
        string $email,
        string $name,
        ?string $avatar,
        bool $emailVerified,
        string $registrationSource = 'web',
        ?string $firstClient = null
    ): User {
        if (! $emailVerified) {
            throw new Exception('Google account email is not verified.');
        }

        // Check for existing OAuth account
        $oauthAccount = OAuthAccount::findByProvider(OAuthAccount::PROVIDER_GOOGLE, $googleId);

        if ($oauthAccount) {
            $user = $oauthAccount->user;

            if (! $user instanceof User) {
                throw new Exception('OAuth account is not linked to a valid user.');
            }

            $this->syncGoogleAccountState($oauthAccount, $user, $googleId, $email, $name, $avatar, $emailVerified);
            $this->fillFirstClient($user, $firstClient);

            return $user;
        }

        // Check for existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->createOAuthAccount($user, $googleId, $email);
            $this->syncUserProfile($user, $name, $email, $avatar, $emailVerified);
            $this->syncOAuthProviderEmail($googleId, $email);
            $this->fillFirstClient($user, $firstClient);

            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'registration_source' => $registrationSource,
            'first_client' => $firstClient,
            'email_verified_at' => now(),
            'password' => null,
        ]);

        $this->createOAuthAccount($user, $googleId, $email);

        return $user;
    }

    /**
     * Sync profile fields and link metadata when an existing OAuth account is found.
     */
    protected function syncGoogleAccountState(
        OAuthAccount $oauthAccount,
        User $user,
        string $googleId,
        string $email,
        string $name,
        ?string $avatar,
        bool $emailVerified
    ): void {
        $this->syncOAuthAccount($oauthAccount, $email, $googleId);
        $this->syncUserProfile($user, $name, $email, $avatar, $emailVerified);
    }

    /**
     * Sync optional user profile fields from Google payload.
     */
    protected function syncUserProfile(
        User $user,
        string $name,
        string $email,
        ?string $avatar,
        bool $emailVerified
    ): void {
        $updates = [];

        if ($name !== '' && $user->name !== $name) {
            $updates['name'] = $name;
        }

        if ($avatar && $user->avatar !== $avatar) {
            $updates['avatar'] = $avatar;
        }

        $normalizedEmail = strtolower(trim($email));

        if ($emailVerified && $normalizedEmail !== '' && strtolower((string) $user->email) !== $normalizedEmail) {
            $conflictingUser = User::where('email', $normalizedEmail)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $conflictingUser) {
                $updates['email'] = $normalizedEmail;
                $updates['email_verified_at'] = now();
            }
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }

    /**
     * Sync provider email and preserve oauth account metadata.
     */
    protected function syncOAuthAccount(OAuthAccount $oauthAccount, string $email, string $googleId): void
    {
        $oauthUpdates = [];

        if ($oauthAccount->provider_id !== $googleId) {
            $oauthUpdates['provider_id'] = $googleId;
        }

        if ($oauthAccount->provider_email !== $email) {
            $oauthUpdates['provider_email'] = $email;
        }

        if (! empty($oauthUpdates)) {
            $oauthAccount->update($oauthUpdates);
        }
    }

    /**
     * Sync OAuth account provider email for fallback user-match flow.
     */
    protected function syncOAuthProviderEmail(string $googleId, string $email): void
    {
        $oauthAccount = OAuthAccount::findByProvider(OAuthAccount::PROVIDER_GOOGLE, $googleId);
        if (! $oauthAccount) {
            return;
        }

        if ($oauthAccount->provider_email !== $email) {
            $oauthAccount->update(['provider_email' => $email]);
        }
    }

    /**
     * Create OAuth account link.
     */
    protected function createOAuthAccount(User $user, string $googleId, string $email): OAuthAccount
    {
        return OAuthAccount::create([
            'user_id' => $user->id,
            'provider' => OAuthAccount::PROVIDER_GOOGLE,
            'provider_id' => $googleId,
            'provider_email' => $email,
        ]);
    }

    private function fillFirstClient(User $user, ?string $firstClient): void
    {
        if ($firstClient && ! $user->first_client) {
            $user->update(['first_client' => $firstClient]);
        }
    }
}
