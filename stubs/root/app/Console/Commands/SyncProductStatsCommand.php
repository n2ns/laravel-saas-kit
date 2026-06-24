<?php

namespace App\Console\Commands;

use App\Services\ProductStatsSyncService;
use Illuminate\Console\Command;

class SyncProductStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-product-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product statistics from external APIs (GitHub, NPM, VSCode)';

    /**
     * Execute the console command.
     */
    public function handle(ProductStatsSyncService $service): void
    {
        $this->info('Starting product stats sync...');

        $results = $service->syncAll();

        foreach ($results as $code => $stats) {
            $this->line("Synced {$code}: ".json_encode($stats));
        }

        $this->info('Sync completed!');
    }
}
