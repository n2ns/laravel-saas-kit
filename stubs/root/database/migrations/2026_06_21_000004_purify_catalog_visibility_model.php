<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('catalog_items', 'show_on_homepage')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->boolean('show_on_homepage')->default(false)->after('is_visible');
                $table->integer('homepage_sort_order')->nullable()->after('show_on_homepage');
                $table->index(['show_on_homepage', 'homepage_sort_order'], 'catalog_items_homepage_idx');
            });
        }

        if (Schema::hasColumn('catalog_item_profiles', 'primary_channel')) {
            Schema::table('catalog_item_profiles', function (Blueprint $table) {
                $table->dropIndex(['primary_channel']);
                $table->dropColumn([
                    'primary_channel',
                    'primary_path',
                    'visibility_channels',
                ]);
            });
        }

        if (Schema::hasColumn('catalog_releases', 'channel')) {
            Schema::table('catalog_releases', function (Blueprint $table) {
                $table->dropIndex(['channel']);
                $table->dropIndex(['site']);
                $table->dropColumn(['channel', 'site']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('catalog_items', 'show_on_homepage')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->dropIndex('catalog_items_homepage_idx');
                $table->dropColumn([
                    'show_on_homepage',
                    'homepage_sort_order',
                ]);
            });
        }
    }
};
