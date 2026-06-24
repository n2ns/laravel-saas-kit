<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_gateways')->updateOrInsert(
            ['code' => 'stripe'],
            [
                'name' => 'Stripe',
                'is_active' => true,
                'config' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('catalog_items')->updateOrInsert(
            ['code' => 'starter'],
            [
                'status' => 'published',
                'sort_order' => 10,
                'is_visible' => true,
                'show_on_homepage' => true,
                'homepage_sort_order' => 10,
                'interest_threshold' => 50,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $catalogItemId = DB::table('catalog_items')->where('code', 'starter')->value('id');

        DB::table('catalog_item_translations')->updateOrInsert(
            [
                'catalog_item_id' => $catalogItemId,
                'locale' => 'en',
            ],
            [
                'name' => 'Starter Product',
                'short_description' => 'A configurable starter product for a new Laravel SaaS site.',
                'long_description' => 'Replace this starter product with your own product copy, media, pricing, and articles from the admin panel.',
                'seo_title' => 'Starter Product',
                'seo_description' => 'A configurable starter product for a Laravel SaaS site.',
                'card_tag' => 'Starter',
                'cta_label' => 'View Product',
                'tags' => json_encode(['Starter', 'SaaS']),
                'key_points' => json_encode([
                    ['title' => 'Google login', 'description' => 'Uses the same Socialite and Google ID token flow as the SaaS Starter main site.'],
                    ['title' => 'Stripe billing', 'description' => 'Includes Checkout, webhooks, orders, subscriptions, and customer portal wiring.'],
                    ['title' => 'Content ready', 'description' => 'Includes blog, product articles, sitemap, and admin publishing workflows.'],
                ]),
                'seo_payload' => json_encode(['indexable' => true, 'schema_type' => 'SoftwareApplication']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('catalog_item_profiles')->updateOrInsert(
            ['catalog_item_id' => $catalogItemId],
            [
                'product_type' => 'web_app',
                'segment' => 'starter',
                'theme_profile' => 'starter',
                'image' => null,
                'icon' => null,
                'thumbnail' => null,
                'version' => null,
                'release_status' => 'stable',
                'development_status' => 'active',
                'media' => json_encode([]),
                'links' => json_encode(['ctas' => [['type' => 'pricing']]]),
                'facts' => json_encode(['code' => 'starter']),
                'aliases' => json_encode([]),
                'seo_payload' => json_encode(['indexable' => true, 'schema_type' => 'SoftwareApplication']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('catalog_item_details')->updateOrInsert(
            ['catalog_item_id' => $catalogItemId],
            [
                'template_key' => 'product-detail-v1',
                'schema_version' => 1,
                'structure_payload' => json_encode([
                    'section_blueprint' => [
                        'hero' => [
                            'icon' => 'layout-dashboard',
                        ],
                    ],
                    'sections' => [
                        [
                            'type' => 'feature-grid',
                            'data_source' => 'features',
                            'icons' => [
                                'auth' => 'shield-check',
                                'billing' => 'credit-card',
                                'content' => 'newspaper',
                            ],
                        ],
                        [
                            'type' => 'text-block',
                            'data_source' => 'article',
                            'title' => 'Template overview',
                        ],
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $detailId = DB::table('catalog_item_details')->where('catalog_item_id', $catalogItemId)->value('id');

        DB::table('catalog_item_detail_translations')->updateOrInsert(
            [
                'catalog_item_detail_id' => $detailId,
                'locale' => 'en',
            ],
            [
                'detail_sections' => json_encode([
                    'features_title' => 'Built-in product-site workflows',
                    'features' => [
                        'auth' => [
                            'title' => 'Authentication',
                            'description' => 'Google web login, Google One Tap, API login, refresh tokens, and device sessions.',
                        ],
                        'billing' => [
                            'title' => 'Billing',
                            'description' => 'Stripe Checkout, webhooks, orders, subscriptions, plans, and customer portal.',
                        ],
                        'content' => [
                            'title' => 'Content and SEO',
                            'description' => 'Product pages, pricing pages, blog posts, product guides, sitemap, and structured data.',
                        ],
                    ],
                    'article' => 'Use this starter product as the first editable entry for a new SaaS site. Replace the copy, plans, Stripe price IDs, legal pages, and product articles from the admin panel.',
                ]),
                'localized_payload' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->assignTaxonomyTerms($catalogItemId);

        DB::table('products')->updateOrInsert(
            ['code' => 'starter'],
            [
                'catalog_item_id' => $catalogItemId,
                'is_active' => true,
                'pause_reason' => null,
                'sort_order' => 10,
                'stripe_product_id' => env('STARTER_STRIPE_PRODUCT_ID'),
                'pricing_page_url' => null,
                'mcp_server_url' => null,
                'mcp_api_key' => null,
                'metadata' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $productId = DB::table('products')->where('code', 'starter')->value('id');

        $plans = [
            [
                'code' => 'starter_free',
                'name' => 'Free',
                'description' => 'Free starter access.',
                'tier' => 'free',
                'billing_cycle' => 'free',
                'price' => 0,
                'features' => ['starter_access' => true],
                'stripe_price_id' => null,
                'sort_order' => 10,
            ],
            [
                'code' => 'starter_plus_monthly',
                'name' => 'Plus',
                'description' => 'Monthly paid access for active users and small teams.',
                'tier' => 'pro',
                'billing_cycle' => 'monthly',
                'price' => 29,
                'features' => ['starter_access' => true, 'support' => 'priority'],
                'stripe_price_id' => env('STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID'),
                'sort_order' => 20,
            ],
            [
                'code' => 'starter_credits_10',
                'name' => '10 Credits',
                'description' => 'One-time credit pack for product-specific paid actions.',
                'tier' => 'addon',
                'billing_cycle' => 'one_time',
                'price' => 2,
                'features' => ['credits' => 10],
                'stripe_price_id' => env('STARTER_CREDITS_10_STRIPE_PRICE_ID'),
                'sort_order' => 30,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['code' => $plan['code']],
                [
                    'product_id' => $productId,
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'tier' => $plan['tier'],
                    'billing_cycle' => $plan['billing_cycle'],
                    'price' => $plan['price'],
                    'currency' => 'USD',
                    'trial_days' => 0,
                    'features' => json_encode($plan['features']),
                    'display_payload' => json_encode([]),
                    'stripe_price_id' => $plan['stripe_price_id'],
                    'is_active' => true,
                    'sort_order' => $plan['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function assignTaxonomyTerms(int $catalogItemId): void
    {
        $assignments = [
            'primary_group' => ['application_product'],
            'platform' => ['web_app'],
            'use_case' => ['saas_workflow', 'content_publishing'],
        ];

        foreach ($assignments as $taxonomyCode => $termCodes) {
            $taxonomyId = DB::table('catalog_taxonomies')->where('code', $taxonomyCode)->value('id');

            if (! $taxonomyId) {
                continue;
            }

            DB::table('catalog_item_taxonomy_terms')
                ->where('catalog_item_id', $catalogItemId)
                ->where('catalog_taxonomy_id', $taxonomyId)
                ->delete();

            foreach ($termCodes as $termCode) {
                $termId = DB::table('catalog_taxonomy_terms')
                    ->where('catalog_taxonomy_id', $taxonomyId)
                    ->where('code', $termCode)
                    ->value('id');

                if (! $termId) {
                    continue;
                }

                DB::table('catalog_item_taxonomy_terms')->insertOrIgnore([
                    'catalog_item_id' => $catalogItemId,
                    'catalog_taxonomy_id' => $taxonomyId,
                    'catalog_taxonomy_term_id' => $termId,
                    'source' => 'seed',
                    'note' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
