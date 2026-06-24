<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;

class ExportSeedDataCommand extends Command
{
    protected $signature = 'seed-data:export
        {--path= : Directory that will receive manifest.json and tables/*.json}
        {--include-migrations : Include Laravel migration metadata}';

    protected $description = 'Export the current database rows to the JSON seed data used by SeedDataSeeder';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $path = $this->seedDataPath();
        $tablesPath = $path.DIRECTORY_SEPARATOR.'tables';

        File::ensureDirectoryExists($tablesPath);
        File::cleanDirectory($tablesPath);

        $excludedTables = $this->option('include-migrations') ? [] : ['migrations'];
        $tables = array_values(array_diff($this->tableNames(), $excludedTables));
        sort($tables);

        $manifestTables = [];

        foreach ($tables as $table) {
            $rows = $this->rowsForTable($table);
            $file = 'tables/'.$table.'.json';

            File::put(
                $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file),
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
            );

            $manifestTables[] = [
                'name' => $table,
                'file' => $file,
                'row_count' => count($rows),
            ];
        }

        $manifest = [
            'schema' => 'database-seed-data.v1',
            'generated_at' => now()->toIso8601String(),
            'connection' => DB::connection()->getDriverName(),
            'database' => DB::connection()->getDatabaseName(),
            'excluded_tables' => $excludedTables,
            'tables' => $manifestTables,
        ];

        File::put(
            $path.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
        );

        $this->info('Seed data written to '.$path);
        $this->info('Exported '.count($manifestTables).' tables.');

        return self::SUCCESS;
    }

    private function seedDataPath(): string
    {
        $path = $this->option('path');

        if (is_string($path) && $path !== '') {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim((string) config('database.seed_data.path'), DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('seed-data:export requires a MySQL connection.');
        }

        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn (object $row): string => (string) array_values((array) $row)[0])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForTable(string $table): array
    {
        $query = DB::table($table);
        $columns = Schema::getColumnListing($table);

        foreach (['id', 'created_at'] as $column) {
            if (in_array($column, $columns, true)) {
                $query->orderBy($column);
            }
        }

        return $query
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }
}
