<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_item_translations', function (Blueprint $table) {
            $table->json('privacy_sections')->nullable()->after('detail_payload');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_item_translations', function (Blueprint $table) {
            $table->dropColumn('privacy_sections');
        });
    }
};
