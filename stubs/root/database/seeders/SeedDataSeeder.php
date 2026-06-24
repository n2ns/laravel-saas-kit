<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;

class SeedDataSeeder extends Seeder
{
    /**
     * @throws JsonException
     */
    public function run(): void
    {
        $this->ensureSafeEnvironment();
        $this->ensureMySqlConnection();

        $path = $this->seedDataPath();

        if (! File::isDirectory($path)) {
            throw new RuntimeException("Seed data directory does not exist: {$path}");
        }

        $tables = $this->tables($path);

        $this->disableForeignKeys();

        try {
            foreach (array_reverse($tables) as $table) {
                $this->clearTable($table['name']);
            }

            foreach ($tables as $table) {
                $this->seedTable($path, $table);
            }
        } finally {
            $this->enableForeignKeys();
        }
    }

    private function seedDataPath(): string
    {
        return rtrim((string) config('database.seed_data.path'), DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<array{name: string, file: string, row_count?: int}>
     *
     * @throws JsonException
     */
    private function tables(string $path): array
    {
        $manifestPath = $path.DIRECTORY_SEPARATOR.'manifest.json';

        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

            return collect($manifest['tables'] ?? [])
                ->reject(fn (array $table): bool => ($table['name'] ?? null) === 'migrations')
                ->map(fn (array $table): array => [
                    'name' => (string) $table['name'],
                    'file' => (string) ($table['file'] ?? 'tables/'.$table['name'].'.json'),
                    'row_count' => $table['row_count'] ?? null,
                ])
                ->values()
                ->all();
        }

        $tablesPath = $path.DIRECTORY_SEPARATOR.'tables';
        if (! File::isDirectory($tablesPath)) {
            throw new RuntimeException("Seed data tables directory does not exist: {$tablesPath}");
        }

        return collect(File::files($tablesPath))
            ->filter(fn ($file): bool => $file->getExtension() === 'json')
            ->map(fn ($file): array => [
                'name' => $file->getBasename('.json'),
                'file' => 'tables/'.$file->getFilename(),
            ])
            ->reject(fn (array $table): bool => $table['name'] === 'migrations')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param  array{name: string, file: string, row_count?: int}  $table
     *
     * @throws JsonException
     */
    private function seedTable(string $path, array $table): void
    {
        $name = $table['name'];

        if (! Schema::hasTable($name)) {
            throw new RuntimeException("Seed data table is missing from the current schema: {$name}");
        }

        $file = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $table['file']);
        if (! File::exists($file)) {
            throw new RuntimeException("Seed data table file is missing: {$file}");
        }

        $rows = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($name)->insert($chunk);
        }

        $this->command?->line('Seeded '.$name.' ('.count($rows).' rows).');
    }

    private function clearTable(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException("Seed data table is missing from the current schema: {$table}");
        }

        if (app()->runningUnitTests()) {
            DB::table($table)->delete();

            return;
        }

        DB::table($table)->truncate();
    }

    private function disableForeignKeys(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    private function enableForeignKeys(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function ensureMySqlConnection(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('SeedDataSeeder requires a MySQL connection.');
        }
    }

    private function ensureSafeEnvironment(): void
    {
        $productionRestoreAllowed = app()->environment('production')
            && (bool) config('database.seed_data.allow_production_restore', false);

        if (! app()->environment(['local', 'testing']) && ! $productionRestoreAllowed) {
            throw new RuntimeException('SeedDataSeeder may only run in local/testing environments or an explicitly allowed production restore.');
        }

    }
}
