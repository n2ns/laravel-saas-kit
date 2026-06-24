<?php

namespace App\Console\Commands;

use Database\Seeders\SeedDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class RestoreSeedDataCommand extends Command
{
    protected $signature = 'database:restore-seed-data
        {--force : Run without confirmation}
        {--allow-production : Allow destructive production restore when APP_ENV=production}';

    protected $description = 'Rebuild the current database from migrations and database/seeders/data';

    public function handle(): int
    {
        $this->ensureSafeTarget();

        if (! $this->option('force') && ! $this->confirm('This will drop and rebuild the current database from seed data. Continue?')) {
            return self::FAILURE;
        }

        config(['database.seed_data.allow_production_restore' => app()->environment('production') && $this->option('allow-production')]);

        try {
            return Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--seeder' => SeedDataSeeder::class,
                '--force' => true,
                '--no-interaction' => true,
            ]);
        } finally {
            config(['database.seed_data.allow_production_restore' => false]);
        }
    }

    private function ensureSafeTarget(): void
    {
        if (app()->environment('production')) {
            if (! $this->option('allow-production')) {
                throw new RuntimeException('database:restore-seed-data requires --allow-production in production.');
            }

            return;
        }

        if (app()->environment('local')) {
            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        throw new RuntimeException('database:restore-seed-data may only run in local, testing, or explicitly allowed production environments.');
    }
}
