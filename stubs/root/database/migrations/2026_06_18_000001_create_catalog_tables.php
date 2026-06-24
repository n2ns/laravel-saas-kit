<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('item_type', 50);
            $table->string('product_type', 50)->nullable();
            $table->string('segment', 100)->nullable();
            $table->string('theme_profile', 100)->nullable();
            $table->string('name')->nullable();
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('card_tag')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('release_status', 50)->default('stable');
            $table->string('development_status', 50)->nullable();
            $table->string('status', 50)->default('published');
            $table->string('template_key', 100)->default('product-detail-v1');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_on_homepage')->default(false);
            $table->integer('homepage_sort_order')->nullable();
            $table->unsignedInteger('interest_threshold')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('tags')->nullable();
            $table->json('key_points')->nullable();
            $table->json('media')->nullable();
            $table->json('links')->nullable();
            $table->json('facts')->nullable();
            $table->json('aliases')->nullable();
            $table->json('seo_payload')->nullable();
            $table->json('detail_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_visible', 'sort_order', 'code'], 'catalog_items_public_listing_idx');
            $table->index(['item_type', 'status', 'is_visible', 'development_status', 'sort_order', 'created_at'], 'catalog_items_public_concepts_idx');
            $table->index('item_type');
            $table->index('product_type');
            $table->index('segment');
            $table->index('release_status');
            $table->index('status');
            $table->index('sort_order');
            $table->index('is_visible');
            $table->index(['show_on_homepage', 'homepage_sort_order'], 'catalog_items_homepage_idx');
            $table->index('published_at');
            $table->index('development_status');
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(
                    ['name', 'short_description', 'long_description', 'seo_title', 'seo_description', 'card_tag', 'cta_label'],
                    'catalog_items_content_fulltext_idx'
                );
            }
        });

        Schema::create('catalog_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name')->nullable();
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('card_tag')->nullable();
            $table->string('cta_label')->nullable();
            $table->json('tags')->nullable();
            $table->json('key_points')->nullable();
            $table->json('seo_payload')->nullable();
            $table->json('detail_sections')->nullable();
            $table->json('detail_payload')->nullable();
            $table->timestamps();

            $table->unique(['catalog_item_id', 'locale']);
            $table->index('locale');
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(
                    ['name', 'short_description', 'long_description', 'seo_title', 'seo_description', 'card_tag', 'cta_label'],
                    'catalog_item_translations_content_fulltext_idx'
                );
            }
        });

        Schema::create('catalog_interest_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->default('en');
            $table->string('status', 50)->default('interested');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['catalog_item_id', 'user_id']);
            $table->index('status');
        });

        Schema::create('catalog_releases', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->nullable()->index();
            $table->string('item_type', 50)->nullable()->index();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('version', 100);
            $table->string('status', 50)->default('draft')->index();
            $table->string('payload_hash', 64)->index();
            $table->string('payload_path')->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->json('filters')->nullable();
            $table->json('metadata')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('exported_at')->nullable()->index();
            $table->timestamp('released_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_releases');
        Schema::dropIfExists('catalog_interest_signups');
        Schema::dropIfExists('catalog_item_translations');
        Schema::dropIfExists('catalog_items');
    }
};
