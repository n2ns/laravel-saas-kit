<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropFullText('catalog_items_content_fulltext_idx');
            }
            $table->dropIndex('catalog_items_public_concepts_idx');
            $table->dropIndex(['item_type']);
            $table->dropIndex(['product_type']);
            $table->dropIndex(['segment']);
            $table->dropIndex(['release_status']);
            $table->dropIndex(['development_status']);

            $table->dropColumn([
                'item_type',
                'product_type',
                'segment',
                'theme_profile',
                'name',
                'short_description',
                'long_description',
                'seo_title',
                'seo_description',
                'card_tag',
                'cta_label',
                'image',
                'icon',
                'thumbnail',
                'version',
                'release_status',
                'development_status',
                'template_key',
                'schema_version',
                'tags',
                'key_points',
                'media',
                'links',
                'facts',
                'aliases',
                'seo_payload',
                'detail_payload',
            ]);
        });

        Schema::table('catalog_item_translations', function (Blueprint $table) {
            $table->dropColumn([
                'detail_sections',
                'detail_payload',
                'privacy_sections',
            ]);
        });

        Schema::table('catalog_releases', function (Blueprint $table) {
            $table->dropIndex(['item_type']);
            $table->dropColumn('item_type');
            $table->string('primary_group', 100)->nullable()->index()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_releases', function (Blueprint $table) {
            $table->dropIndex(['primary_group']);
            $table->dropColumn('primary_group');
            $table->string('item_type', 50)->nullable()->index()->after('locale');
        });

        Schema::table('catalog_item_translations', function (Blueprint $table) {
            $table->json('detail_sections')->nullable()->after('seo_payload');
            $table->json('detail_payload')->nullable()->after('detail_sections');
            $table->json('privacy_sections')->nullable()->after('detail_payload');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('item_type', 50)->after('code');
            $table->string('product_type', 50)->nullable()->after('item_type');
            $table->string('segment', 100)->nullable()->after('product_type');
            $table->string('theme_profile', 100)->nullable()->after('segment');
            $table->string('name')->nullable()->after('theme_profile');
            $table->text('short_description')->nullable()->after('name');
            $table->text('long_description')->nullable()->after('short_description');
            $table->string('seo_title')->nullable()->after('long_description');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('card_tag')->nullable()->after('seo_description');
            $table->string('cta_label')->nullable()->after('card_tag');
            $table->string('image')->nullable()->after('cta_label');
            $table->string('icon')->nullable()->after('image');
            $table->string('thumbnail')->nullable()->after('icon');
            $table->string('version', 50)->nullable()->after('thumbnail');
            $table->string('release_status', 50)->default('stable')->after('version');
            $table->string('development_status', 50)->nullable()->after('release_status');
            $table->string('template_key', 100)->default('product-detail-v1')->after('status');
            $table->unsignedSmallInteger('schema_version')->default(1)->after('template_key');
            $table->json('tags')->nullable()->after('published_at');
            $table->json('key_points')->nullable()->after('tags');
            $table->json('media')->nullable()->after('key_points');
            $table->json('links')->nullable()->after('media');
            $table->json('facts')->nullable()->after('links');
            $table->json('aliases')->nullable()->after('facts');
            $table->json('seo_payload')->nullable()->after('aliases');
            $table->json('detail_payload')->nullable()->after('seo_payload');

            $table->index(['item_type', 'status', 'is_visible', 'development_status', 'sort_order', 'created_at'], 'catalog_items_public_concepts_idx');
            $table->index('item_type');
            $table->index('product_type');
            $table->index('segment');
            $table->index('release_status');
            $table->index('development_status');
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(
                    ['name', 'short_description', 'long_description', 'seo_title', 'seo_description', 'card_tag', 'cta_label'],
                    'catalog_items_content_fulltext_idx'
                );
            }
        });
    }
};
