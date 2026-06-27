<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class PassportSocialClientCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_configures_existing_passport_client_for_social_grant(): void
    {
        config([
            'passport.password_client.id' => 'client-test-id',
            'passport.password_client.secret' => 'plain-secret',
        ]);

        DB::table('oauth_clients')->insert([
            'id' => 'client-test-id',
            'name' => 'API Client',
            'secret' => 'secret',
            'provider' => 'users',
            'redirect_uris' => '[]',
            'grant_types' => json_encode(['password', 'refresh_token']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('passport:ensure-social-client')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', [
            'id' => 'client-test-id',
            'grant_types' => json_encode(['social', 'refresh_token']),
        ]);
    }

    public function test_command_fails_when_existing_client_secret_is_not_configured(): void
    {
        config([
            'passport.password_client.id' => 'client-test-id',
            'passport.password_client.secret' => null,
        ]);

        DB::table('oauth_clients')->insert([
            'id' => 'client-test-id',
            'name' => 'API Client',
            'secret' => 'secret',
            'provider' => 'users',
            'redirect_uris' => '[]',
            'grant_types' => json_encode(['social', 'refresh_token']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('passport:ensure-social-client')
            ->expectsOutput('PASSPORT_PASSWORD_CLIENT_SECRET is not configured.')
            ->assertExitCode(1);
    }

    public function test_command_can_create_initial_confidential_social_client(): void
    {
        config(['passport.password_client.id' => null]);

        $this->artisan('passport:ensure-social-client --create')
            ->expectsOutput('Passport social grant client created.')
            ->assertExitCode(0);

        $client = DB::table('oauth_clients')->first();

        $this->assertNotNull($client);
        $this->assertSame('["social","refresh_token"]', $client->grant_types);
        $this->assertNotNull($client->secret);
    }

    public function test_command_allows_oauth_social_grant_for_configured_client(): void
    {
        $this->configurePassportKeys();

        $client = app(ClientRepository::class)->createPasswordGrantClient('API Client', 'users', true);

        config([
            'passport.password_client.id' => $client->getKey(),
            'passport.password_client.secret' => $client->plainSecret,
        ]);

        DB::table('oauth_clients')->where('id', $client->getKey())->update([
            'grant_types' => json_encode(['password', 'refresh_token']),
        ]);

        $this->artisan('passport:ensure-social-client')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', [
            'id' => $client->getKey(),
            'grant_types' => json_encode(['social', 'refresh_token']),
        ]);

        $this->post('/oauth/token', $this->socialGrantPayload())
            ->assertBadRequest()
            ->assertJsonPath('error', 'invalid_grant');
    }

    /**
     * @return array<string, string>
     */
    private function socialGrantPayload(): array
    {
        return [
            'grant_type' => 'social',
            'client_id' => (string) config('passport.password_client.id'),
            'client_secret' => (string) config('passport.password_client.secret'),
            'provider' => 'google',
            'access_token' => 'not-a-google-id-token',
            'source_client' => 'chrome_starter',
            'scope' => 'api',
        ];
    }

    private function configurePassportKeys(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($privateKey);
        $this->assertTrue(openssl_pkey_export($privateKey, $privateKeyContents));

        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);

        config([
            'passport.private_key' => $privateKeyContents,
            'passport.public_key' => $details['key'],
        ]);
    }
}
