<?php

namespace App\Console\Commands;

use App\Services\CatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

class ExportCatalogCommand extends Command
{
    protected $signature = 'catalog:export
        {--primary-group= : Product taxonomy primary group filter}
        {--locale= : Locale or URL prefix}
        {--q= : Search catalog text before exporting}
        {--path= : Write the JSON payload to this file}
        {--record : Store a catalog release row}
        {--pretty : Pretty-print JSON}';

    protected $description = 'Export structured catalog data for site builds or external consumers';

    /**
     * @throws JsonException
     */
    public function handle(CatalogService $catalogService): int
    {
        $filters = array_filter([
            'primary_group' => $this->option('primary-group'),
            'locale' => $this->option('locale'),
            'q' => $this->option('q'),
        ], fn ($value): bool => $value !== null && $value !== '');

        $payload = $catalogService->export($filters);
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($this->option('pretty')) {
            $jsonFlags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($payload, $jsonFlags | JSON_THROW_ON_ERROR);
        $path = $this->option('path');

        if (is_string($path) && $path !== '') {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $json.PHP_EOL);
            $this->info("Catalog export written to {$path}");
            $this->info('Exported '.count($payload['items']).' catalog items.');
        } else {
            $this->line($json);
        }

        if ($this->option('record')) {
            $catalogService->recordRelease($payload, $filters, is_string($path) && $path !== '' ? $path : null);
        }

        return Command::SUCCESS;
    }
}
