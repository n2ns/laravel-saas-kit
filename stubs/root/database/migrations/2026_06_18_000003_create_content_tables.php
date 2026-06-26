<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('pin_order')->default(0);
            $table->timestamp('pinned_until')->nullable();
            $table->string('type')->default('article');
            $table->json('geo_tags')->nullable();
            $table->json('topics')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->json('related_slugs')->nullable();
            $table->string('thumbnail')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_pinned', 'pin_order', 'published_at'], 'blog_posts_listing_order_idx');
            $table->index(['status', 'published_at'], 'blog_posts_status_published_idx');
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'excerpt', 'content'], 'blog_posts_content_fulltext_idx');
            }
        });

        Schema::create('blog_post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->timestamps();

            $table->unique(['blog_post_id', 'locale']);
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'excerpt', 'content'], 'blog_post_translations_content_fulltext_idx');
            }
        });

        Schema::create('site_visit_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('locale', 10)->index();
            $table->string('path', 2048);
            $table->string('path_hash', 64);
            $table->string('route_name', 120)->nullable()->index();
            $table->string('page_type', 40)->index();
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blog_post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key', 64);
            $table->string('source_type', 20)->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('referrer_host', 191)->nullable()->index();
            $table->string('utm_source', 100)->nullable()->index();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['visit_date', 'locale', 'path_hash', 'source_key'], 'site_visit_daily_unique');
            $table->index(['visit_date', 'source_type'], 'site_visit_source_index');
            $table->index(['visit_date', 'page_type'], 'site_visit_page_type_index');
        });

        Schema::create('site_visit_uniques', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('locale', 10);
            $table->string('path_hash', 64);
            $table->string('source_key', 64);
            $table->string('visitor_hash', 64);
            $table->timestamps();

            $table->unique(['visit_date', 'locale', 'path_hash', 'source_key', 'visitor_hash'], 'site_visit_unique_visitor');
            $table->index(['visit_date', 'locale'], 'site_visit_unique_date_locale');
        });

        Schema::create('site_event_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->string('locale', 10)->index();
            $table->string('event_name', 80)->index();
            $table->string('event_type', 40)->index();
            $table->string('path', 2048)->nullable();
            $table->string('path_hash', 64);
            $table->string('target_url', 2048)->nullable();
            $table->string('target_hash', 64);
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blog_post_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('events')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['event_date', 'locale', 'event_name', 'event_type', 'path_hash', 'target_hash'], 'site_event_daily_unique');
            $table->index(['event_date', 'event_type'], 'site_event_type_index');
        });

        Schema::create('site_event_uniques', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->string('locale', 10);
            $table->string('event_key', 64);
            $table->string('visitor_hash', 64);
            $table->timestamps();

            $table->unique(['event_date', 'locale', 'event_key', 'visitor_hash'], 'site_event_unique_visitor');
            $table->index(['event_date', 'locale'], 'site_event_unique_date_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_event_uniques');
        Schema::dropIfExists('site_event_daily_stats');
        Schema::dropIfExists('site_visit_uniques');
        Schema::dropIfExists('site_visit_daily_stats');
        Schema::dropIfExists('blog_post_translations');
        Schema::dropIfExists('blog_posts');
    }
};
