<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

class EnsurePassportSocialClientCommand extends Command
{
    protected $signature = 'passport:ensure-social-client
        {--create : Create a confidential Passport client when PASSPORT_PASSWORD_CLIENT_ID is not configured}
        {--name=SaaS Kit API Social Grant Client : Name for the created Passport client}
        {--provider=users : User provider for the created Passport client}';

    protected $description = 'Ensure the configured Passport API client can issue social grant tokens';

    public function handle(): int
    {
        if (! Schema::hasTable('oauth_clients')) {
            $this->error('The oauth_clients table does not exist. Run migrations first.');

            return Command::FAILURE;
        }

        $clientId = config('passport.password_client.id');

        if (! is_string($clientId) || $clientId === '') {
            if (! $this->option('create')) {
                $this->error('PASSPORT_PASSWORD_CLIENT_ID is not configured.');
                $this->line('For first setup, run: php artisan passport:ensure-social-client --create');

                return Command::FAILURE;
            }

            $client = app(ClientRepository::class)->createPasswordGrantClient(
                (string) $this->option('name'),
                (string) $this->option('provider'),
                confidential: true
            );

            $this->setSocialGrantTypes((string) $client->getKey());

            $this->info('Passport social grant client created.');
            $this->line('Add these values to .env, then rebuild the config cache:');
            $this->line('PASSPORT_PASSWORD_CLIENT_ID='.$client->getKey());
            $this->line('PASSPORT_PASSWORD_CLIENT_SECRET='.$client->plainSecret);

            return Command::SUCCESS;
        }

        $clientSecret = config('passport.password_client.secret');

        if (! is_string($clientSecret) || $clientSecret === '') {
            $this->error('PASSPORT_PASSWORD_CLIENT_SECRET is not configured.');
            $this->line('Copy the secret printed by php artisan passport:ensure-social-client --create into .env.');

            return Command::FAILURE;
        }

        $client = DB::table('oauth_clients')->where('id', $clientId)->first();

        if (! $client) {
            $this->error("Passport client [{$clientId}] was not found.");

            return Command::FAILURE;
        }

        $grantTypes = json_decode((string) $client->grant_types, true);
        $grantTypes = is_array($grantTypes) ? array_values(array_filter($grantTypes, 'is_string')) : [];
        $expectedGrantTypes = ['social', 'refresh_token'];

        if ($grantTypes === $expectedGrantTypes) {
            $this->info('Passport social grant client is already configured.');

            return Command::SUCCESS;
        }

        $this->setSocialGrantTypes($clientId);
        $this->info('Passport social grant client grant_types updated to social and refresh_token.');

        return Command::SUCCESS;
    }

    private function setSocialGrantTypes(string $clientId): void
    {
        DB::table('oauth_clients')
            ->where('id', $clientId)
            ->update([
                'grant_types' => json_encode(['social', 'refresh_token']),
                'updated_at' => now(),
            ]);
    }
}
