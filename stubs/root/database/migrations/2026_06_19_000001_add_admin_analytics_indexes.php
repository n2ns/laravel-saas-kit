<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $productUsageDailyTables = [
        'product_usage_daily_starter',
    ];

    public function up(): void
    {
        foreach ($this->productUsageDailyTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->index('date', "{$tableName}_date_idx");
            });
        }

        Schema::table('site_visit_daily_stats', function (Blueprint $table): void {
            $table->index(['visit_date', 'path_hash'], 'site_visit_date_path_hash_idx');
            $table->index(['visit_date', 'locale'], 'site_visit_date_locale_idx');
            $table->index(['visit_date', 'country_code'], 'site_visit_date_country_idx');
            $table->index(['visit_date', 'referrer_host'], 'site_visit_date_referrer_idx');
            $table->index(['visit_date', 'utm_source', 'utm_medium', 'utm_campaign'], 'site_visit_date_utm_idx');
        });

        Schema::table('site_event_daily_stats', function (Blueprint $table): void {
            $table->index(['event_date', 'catalog_item_id'], 'site_event_date_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::table('site_event_daily_stats', function (Blueprint $table): void {
            $table->dropIndex('site_event_date_catalog_idx');
        });

        Schema::table('site_visit_daily_stats', function (Blueprint $table): void {
            $table->dropIndex('site_visit_date_utm_idx');
            $table->dropIndex('site_visit_date_referrer_idx');
            $table->dropIndex('site_visit_date_country_idx');
            $table->dropIndex('site_visit_date_locale_idx');
            $table->dropIndex('site_visit_date_path_hash_idx');
        });

        foreach ($this->productUsageDailyTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_date_idx");
            });
        }
    }
};
