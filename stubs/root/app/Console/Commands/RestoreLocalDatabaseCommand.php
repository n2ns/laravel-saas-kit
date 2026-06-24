<?php

namespace App\Console\Commands;

use Database\Seeders\SeedDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class RestoreLocalDatabaseCommand extends Command
{
    protected $signature = 'database:restore-local {--force : Run without confirmation}';

    protected $description = 'Rebuild the local development database from migrations and seed data';

    public function handle(): int
    {
        $this->ensureSafeTarget();

        if (! $this->option('force') && ! $this->confirm('This will drop and rebuild the local development database. Continue?')) {
            return self::FAILURE;
        }

        return Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--seeder' => SeedDataSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    private function ensureSafeTarget(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('database:restore-local may only run in the local environment.');
        }

    }
}
