<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{code: string, name: string, description: string, selection_mode: string, is_public_filter: bool, terms: array<int, array{code: string, name: string, description: string}>}>
     */
    private array $taxonomies = [
        'primary_group' => [
            'code' => 'primary_group',
            'name' => 'Primary group',
            'description' => 'The first-level product list grouping used for public catalog navigation.',
            'selection_mode' => 'single',
            'is_public_filter' => true,
            'terms' => [
                ['code' => 'application_product', 'name' => 'Application Product', 'description' => 'End-user applications and browser-based products.'],
                ['code' => 'developer_tool', 'name' => 'Developer Tool', 'description' => 'Tools, extensions, servers, and packages for developer workflows.'],
            ],
        ],
        'platform' => [
            'code' => 'platform',
            'name' => 'Platform',
            'description' => 'Where the product or tool is primarily used.',
            'selection_mode' => 'single',
            'is_public_filter' => true,
            'terms' => [
                ['code' => 'chrome_extension', 'name' => 'Chrome Extension', 'description' => 'Runs as a Chrome or Chromium-compatible browser extension.'],
                ['code' => 'vscode_extension', 'name' => 'VS Code Extension', 'description' => 'Runs inside VS Code or compatible editor surfaces.'],
                ['code' => 'mcp_server', 'name' => 'MCP Server', 'description' => 'Exposes tools or context through the Model Context Protocol.'],
                ['code' => 'laravel_package', 'name' => 'Laravel Package', 'description' => 'Installs into Laravel applications.'],
                ['code' => 'web_app', 'name' => 'Web App', 'description' => 'Runs primarily as a web application.'],
                ['code' => 'ide_extension', 'name' => 'IDE Extension', 'description' => 'Runs inside IDE or editor extension surfaces.'],
            ],
        ],
        'use_case' => [
            'code' => 'use_case',
            'name' => 'Use case',
            'description' => 'The main customer workflow or job-to-be-done.',
            'selection_mode' => 'multiple',
            'is_public_filter' => true,
            'terms' => [
                ['code' => 'ai_workflow', 'name' => 'AI Workflow', 'description' => 'Helps users operate, review, or automate AI-assisted work.'],
                ['code' => 'automation', 'name' => 'Automation', 'description' => 'Automates repetitive manual tasks or form-based workflows.'],
                ['code' => 'browser_enhancement', 'name' => 'Browser Enhancement', 'description' => 'Improves browser-side reading, clipping, or productivity workflows.'],
                ['code' => 'knowledge_management', 'name' => 'Knowledge Management', 'description' => 'Captures, organizes, or retrieves knowledge and memory.'],
                ['code' => 'developer_integration', 'name' => 'Developer Integration', 'description' => 'Connects tools, editors, repositories, or agent workflows.'],
                ['code' => 'content_publishing', 'name' => 'Content Publishing', 'description' => 'Moves drafted content into websites, blogs, or publishing systems.'],
                ['code' => 'saas_workflow', 'name' => 'SaaS Workflow', 'description' => 'Supports productized software workflows.'],
            ],
        ],
    ];

    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private array $itemTerms = [
        'starter' => [
            'primary_group' => ['application_product'],
            'platform' => ['web_app'],
            'use_case' => ['saas_workflow', 'content_publishing'],
        ],
    ];

    public function up(): void
    {
        Schema::create('catalog_item_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('product_type', 50)->nullable();
            $table->string('segment', 100)->nullable();
            $table->string('theme_profile', 100)->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('release_status', 50)->default('stable');
            $table->string('development_status', 50)->nullable();
            $table->json('media')->nullable();
            $table->json('links')->nullable();
            $table->json('facts')->nullable();
            $table->json('aliases')->nullable();
            $table->json('seo_payload')->nullable();
            $table->timestamps();

            $table->index('product_type');
            $table->index('segment');
            $table->index('release_status');
            $table->index('development_status');
        });

        Schema::create('catalog_item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('template_key', 100)->default('product-detail-v1');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->json('structure_payload')->nullable();
            $table->timestamps();

            $table->index('template_key');
        });

        Schema::create('catalog_item_detail_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_detail_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->json('detail_sections')->nullable();
            $table->json('localized_payload')->nullable();
            $table->timestamps();

            $table->unique(['catalog_item_detail_id', 'locale'], 'catalog_detail_translations_locale_unique');
            $table->index('locale');
        });

        Schema::create('catalog_item_privacy_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->string('updated_label')->nullable();
            $table->date('effective_date')->nullable();
            $table->json('sections')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['catalog_item_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('catalog_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('selection_mode', 20)->default('single');
            $table->boolean('is_public_filter')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_taxonomy_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('ai_definition')->nullable();
            $table->json('examples')->nullable();
            $table->json('negative_examples')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_taxonomy_id', 'code']);
            $table->index('code');
        });

        Schema::create('catalog_item_taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_taxonomy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_taxonomy_term_id')->constrained()->cascadeOnDelete();
            $table->string('source', 50)->default('manual');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['catalog_item_id', 'catalog_taxonomy_term_id'], 'catalog_item_term_unique');
            $table->index(['catalog_item_id', 'catalog_taxonomy_id'], 'catalog_item_taxonomy_idx');
        });

        $this->backfillSplitTables();
        $this->seedTaxonomiesAndTerms();
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_taxonomy_terms');
        Schema::dropIfExists('catalog_taxonomy_terms');
        Schema::dropIfExists('catalog_taxonomies');
        Schema::dropIfExists('catalog_item_privacy_policies');
        Schema::dropIfExists('catalog_item_detail_translations');
        Schema::dropIfExists('catalog_item_details');
        Schema::dropIfExists('catalog_item_profiles');
    }

    private function backfillSplitTables(): void
    {
        $now = now();

        foreach (DB::table('catalog_items')->orderBy('id')->cursor() as $item) {
            DB::table('catalog_item_profiles')->insert([
                'catalog_item_id' => $item->id,
                'product_type' => $item->product_type,
                'segment' => $item->segment,
                'theme_profile' => $item->theme_profile,
                'image' => $item->image,
                'icon' => $item->icon,
                'thumbnail' => $item->thumbnail,
                'version' => $item->version,
                'release_status' => $item->release_status,
                'development_status' => $item->development_status,
                'media' => $item->media,
                'links' => $item->links,
                'facts' => $item->facts,
                'aliases' => $item->aliases,
                'seo_payload' => $item->seo_payload,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('catalog_item_details')->insert([
                'catalog_item_id' => $item->id,
                'template_key' => $item->template_key,
                'schema_version' => $item->schema_version,
                'structure_payload' => $item->detail_payload,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $detailIds = DB::table('catalog_item_details')->pluck('id', 'catalog_item_id')->all();

        foreach (DB::table('catalog_item_translations')->orderBy('id')->cursor() as $translation) {
            $detailId = $detailIds[$translation->catalog_item_id] ?? null;
            if ($detailId !== null && ($translation->detail_sections !== null || $translation->detail_payload !== null)) {
                DB::table('catalog_item_detail_translations')->insert([
                    'catalog_item_detail_id' => $detailId,
                    'locale' => $translation->locale,
                    'detail_sections' => $translation->detail_sections,
                    'localized_payload' => $translation->detail_payload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $privacy = $this->decodeJson($translation->privacy_sections ?? null);
            $sections = is_array($privacy) ? ($privacy['sections'] ?? null) : null;
            if (! empty($sections)) {
                $metadata = $privacy;
                unset($metadata['sections'], $metadata['title'], $metadata['updated']);

                DB::table('catalog_item_privacy_policies')->insert([
                    'catalog_item_id' => $translation->catalog_item_id,
                    'locale' => $translation->locale,
                    'title' => is_string($privacy['title'] ?? null) ? $privacy['title'] : null,
                    'updated_label' => is_string($privacy['updated'] ?? null) ? $privacy['updated'] : null,
                    'effective_date' => null,
                    'sections' => $this->encodeJson($sections),
                    'metadata' => $metadata === [] ? null : $this->encodeJson($metadata),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedTaxonomiesAndTerms(): void
    {
        $now = now();
        $taxonomyIds = [];
        $termIds = [];
        $sort = 0;

        foreach ($this->taxonomies as $taxonomyCode => $taxonomy) {
            DB::table('catalog_taxonomies')->insert([
                'code' => $taxonomy['code'],
                'name' => $taxonomy['name'],
                'description' => $taxonomy['description'],
                'selection_mode' => $taxonomy['selection_mode'],
                'is_public_filter' => $taxonomy['is_public_filter'],
                'sort_order' => $sort++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $taxonomyId = (int) DB::getPdo()->lastInsertId();
            $taxonomyIds[$taxonomyCode] = $taxonomyId;
            $termSort = 0;

            foreach ($taxonomy['terms'] as $term) {
                DB::table('catalog_taxonomy_terms')->insert([
                    'catalog_taxonomy_id' => $taxonomyId,
                    'code' => $term['code'],
                    'name' => $term['name'],
                    'description' => $term['description'],
                    'ai_definition' => $term['description'],
                    'examples' => null,
                    'negative_examples' => null,
                    'is_public' => true,
                    'sort_order' => $termSort++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $termIds[$taxonomyCode][$term['code']] = (int) DB::getPdo()->lastInsertId();
            }
        }

        $itemIds = DB::table('catalog_items')->pluck('id', 'code')->all();

        foreach ($this->itemTerms as $code => $taxonomyTerms) {
            $itemId = $itemIds[$code] ?? null;
            if ($itemId === null) {
                continue;
            }

            foreach ($taxonomyTerms as $taxonomyCode => $termCodes) {
                $taxonomyId = $taxonomyIds[$taxonomyCode] ?? null;
                if ($taxonomyId === null) {
                    continue;
                }

                foreach ($termCodes as $termCode) {
                    $termId = $termIds[$taxonomyCode][$termCode] ?? null;
                    if ($termId === null) {
                        continue;
                    }

                    DB::table('catalog_item_taxonomy_terms')->insert([
                        'catalog_item_id' => $itemId,
                        'catalog_taxonomy_id' => $taxonomyId,
                        'catalog_taxonomy_term_id' => $termId,
                        'source' => 'manual',
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
};
