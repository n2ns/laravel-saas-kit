<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteAccessAnalytics;
use App\Filament\Resources\CatalogItemResource;
use App\Filament\Resources\HomepageDisplayResource;
use App\Filament\Resources\HomepageDisplayResource\Pages\EditHomepageDisplay;
use App\Filament\Resources\HomepageDisplayResource\Pages\ListHomepageDisplays;
use App\Models\ApiKey;
use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Models\OAuthAccount;
use App\Models\Order;
use App\Models\Plan;
use App\Models\SiteVisitDailyStat;
use App\Models\User;
use App\Services\Auth\SocialAuthService;
use App\Services\StripeService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class TemplateSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_starter_reference_data_is_seeded(): void
    {
        $this->seed();

        $this->assertDatabaseHas('catalog_items', [
            'code' => 'starter',
        ]);

        $this->assertDatabaseHas('catalog_item_translations', [
            'name' => 'Starter Product',
        ]);

        $this->assertDatabaseHas('plans', [
            'code' => 'starter_plus_monthly',
            'name' => 'Plus',
        ]);

        $this->assertDatabaseHas('plans', [
            'code' => 'starter_credits_10',
            'name' => '10 Credits',
        ]);
    }

    public function test_public_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_single_product_home_page_links_to_pricing(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Pricing')
            ->assertSee('/starter/pricing', false)
            ->assertSee('View pricing');
    }

    public function test_google_auth_redirect_uses_stateful_socialite_flow(): void
    {
        Socialite::shouldReceive('driver->scopes->redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com'));

        $this->get('/auth/google')
            ->assertRedirect('https://accounts.google.com');
    }

    public function test_starter_pricing_page_loads(): void
    {
        $this->seed();

        $this->get('/starter/pricing')
            ->assertOk()
            ->assertSee('Starter Product')
            ->assertSee('Plus')
            ->assertSee('10 Credits')
            ->assertDontSee('/checkout/starter/starter_free', false);
    }

    public function test_starter_product_detail_page_loads(): void
    {
        $this->seed();

        $this->get('/starter')
            ->assertOk()
            ->assertSee('Starter Product')
            ->assertSee('Built-in product-site workflows')
            ->assertSee('Authentication');
    }

    public function test_default_admin_email_does_not_grant_admin_access(): void
    {
        config(['app.admin_emails' => []]);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->assertFalse($user->isAdmin());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $service = app(SocialAuthService::class);
        $googleUser = new class
        {
            public function getId(): string
            {
                return 'google-user-1';
            }

            public function getEmail(): string
            {
                return 'person@example.com';
            }

            public function getName(): string
            {
                return 'Person';
            }

            public function getAvatar(): ?string
            {
                return null;
            }

            /**
             * @return array<string, mixed>
             */
            public function getRaw(): array
            {
                return ['email_verified' => false];
            }
        };

        $this->expectException(Exception::class);

        $service->authenticateUser($googleUser);
    }

    public function test_social_auth_records_first_client_for_new_google_user(): void
    {
        $service = new SocialAuthService;

        $user = $service->createGoogleOneTapUser(
            'google-first-client-1',
            'first-client@example.com',
            'First Client',
            null,
            true,
            'web-onetap',
            'chrome_starter'
        );

        $this->assertSame('chrome_starter', $user->first_client);
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider_id' => 'google-first-client-1',
        ]);
    }

    public function test_google_profile_sync_updates_existing_user_when_data_changes(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'avatar' => null,
        ]);

        OAuthAccount::create([
            'user_id' => $user->id,
            'provider' => OAuthAccount::PROVIDER_GOOGLE,
            'provider_id' => 'google-sync-user',
            'provider_email' => 'old@example.com',
        ]);

        $service = app(SocialAuthService::class);

        $googleUser = new class
        {
            public function getId(): string
            {
                return 'google-sync-user';
            }

            public function getEmail(): string
            {
                return 'newname@example.com';
            }

            public function getName(): string
            {
                return 'New Display Name';
            }

            public function getAvatar(): ?string
            {
                return 'https://example.com/new-avatar.png';
            }

            /**
             * @return array<string, mixed>
             */
            public function getRaw(): array
            {
                return ['email_verified' => true];
            }
        };

        $updatedUser = $service->authenticateUser($googleUser);

        $this->assertSame($user->id, $updatedUser->id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Display Name',
            'email' => 'newname@example.com',
            'avatar' => 'https://example.com/new-avatar.png',
        ]);
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider_id' => 'google-sync-user',
            'provider_email' => 'newname@example.com',
        ]);
    }

    public function test_google_profile_email_conflict_does_not_update_main_user_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'owner@example.com',
            'avatar' => null,
        ]);

        User::factory()->create([
            'email' => 'owner-new@example.com',
        ]);

        OAuthAccount::create([
            'user_id' => $user->id,
            'provider' => OAuthAccount::PROVIDER_GOOGLE,
            'provider_id' => 'google-conflict-user',
            'provider_email' => 'owner@example.com',
        ]);

        $service = app(SocialAuthService::class);

        $googleUser = new class
        {
            public function getId(): string
            {
                return 'google-conflict-user';
            }

            public function getEmail(): string
            {
                return 'owner-new@example.com';
            }

            public function getName(): string
            {
                return 'Changed Name';
            }

            public function getAvatar(): ?string
            {
                return 'https://example.com/avatar.png';
            }

            /**
             * @return array<string, mixed>
             */
            public function getRaw(): array
            {
                return ['email_verified' => true];
            }
        };

        $updatedUser = $service->authenticateUser($googleUser);

        $this->assertSame($user->id, $updatedUser->id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Changed Name',
            'email' => 'owner@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider_id' => 'google-conflict-user',
            'provider_email' => 'owner-new@example.com',
        ]);
        $this->assertDatabaseMissing('oauth_accounts', [
            'user_id' => $user->id,
            'provider_id' => 'google-conflict-user',
            'provider_email' => 'owner@example.com',
        ]);
    }

    public function test_google_fallback_login_uses_matching_email_user_when_google_id_is_unknown(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Fallback User',
            'email' => 'fallback-owner@example.com',
            'avatar' => null,
        ]);

        $otherUser = User::factory()->create([
            'email' => 'fallback-new@example.com',
            'name' => 'Other Email User',
        ]);

        $service = app(SocialAuthService::class);

        $googleUser = new class
        {
            public function getId(): string
            {
                return 'google-fallback-conflict';
            }

            public function getEmail(): string
            {
                return 'fallback-new@example.com';
            }

            public function getName(): string
            {
                return 'Fallback Changed Name';
            }

            public function getAvatar(): ?string
            {
                return 'https://example.com/fallback-avatar.png';
            }

            /**
             * @return array<string, mixed>
             */
            public function getRaw(): array
            {
                return ['email_verified' => true];
            }
        };

        $updatedUser = $service->authenticateUser($googleUser);

        $this->assertSame($otherUser->id, $updatedUser->id);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'Fallback Changed Name',
            'email' => 'fallback-new@example.com',
            'avatar' => 'https://example.com/fallback-avatar.png',
        ]);
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $otherUser->id,
            'provider' => OAuthAccount::PROVIDER_GOOGLE,
            'provider_id' => 'google-fallback-conflict',
            'provider_email' => 'fallback-new@example.com',
        ]);

        $this->assertDatabaseMissing('oauth_accounts', [
            'user_id' => $targetUser->id,
            'provider_id' => 'google-fallback-conflict',
        ]);
    }

    public function test_api_exception_response_hides_internal_message_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        Route::get('/api/internal-test-error', function () {
            throw new RuntimeException('secret implementation detail');
        });

        $this->getJson('/api/internal-test-error')
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => 'ServerError',
                'message' => 'Internal Server Error',
            ])
            ->assertJsonMissing([
                'error' => 'RuntimeException',
                'message' => 'secret implementation detail',
            ]);
    }

    public function test_api_key_authentication_is_request_scoped(): void
    {
        Route::middleware('api_key')->get('/api/test-api-key-user', function (Request $request) {
            return response()->json([
                'request_user_id' => $request->user()?->id,
                'auth_user_id' => Auth::id(),
            ]);
        });

        Route::get('/api/test-auth-state', fn () => response()->json([
            'authenticated' => Auth::check(),
        ]));

        $user = User::factory()->create();
        $key = ApiKey::generate($user, 'Test key');

        $this->withHeader('X-Api-Key', $key)
            ->getJson('/api/test-api-key-user')
            ->assertOk()
            ->assertJson([
                'request_user_id' => $user->id,
                'auth_user_id' => $user->id,
            ]);

        $this->getJson('/api/test-auth-state')
            ->assertOk()
            ->assertJson([
                'authenticated' => false,
            ]);
    }

    public function test_localized_blog_post_visit_tracks_blog_context(): void
    {
        $user = User::factory()->create();
        $post = BlogPost::create([
            'title' => 'Localized Tracked Article',
            'slug' => 'localized-tracked-article',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => 'Article content.',
            'excerpt' => 'Article excerpt.',
            'type' => 'technical',
            'content_scope' => null,
            'user_id' => $user->id,
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get('/es/blog/localized-tracked-article')
            ->assertOk();

        $this->assertDatabaseHas('site_visit_daily_stats', [
            'path' => '/es/blog/localized-tracked-article',
            'locale' => 'es',
            'route_name' => 'localized.blog.show',
            'page_type' => 'blog_post',
            'blog_post_id' => $post->id,
        ]);
    }

    public function test_site_access_page_supports_24_hour_and_15_day_ranges(): void
    {
        $page = new SiteAccessAnalytics;
        $range = new ReflectionMethod($page, 'resolvedDateRange');
        $label = new ReflectionMethod($page, 'rangeLabel');

        $page->dateRange = '1';

        $this->assertSame(1, $range->invoke($page));
        $this->assertSame('最近 24 小时', $label->invoke($page, 1));

        $page->dateRange = '15';

        $this->assertSame(15, $range->invoke($page));
        $this->assertSame('最近 15 天', $label->invoke($page, 15));
    }

    public function test_site_access_top_pages_merge_historical_route_types_for_same_path(): void
    {
        $pathHash = hash('sha256', '/');
        $date = now()->toDateString();

        SiteVisitDailyStat::create([
            'visit_date' => $date,
            'locale' => 'en',
            'path' => '/',
            'path_hash' => $pathHash,
            'route_name' => 'home',
            'page_type' => 'home',
            'source_key' => 'direct',
            'source_type' => 'direct',
            'views' => 671,
            'unique_visitors' => 616,
        ]);

        SiteVisitDailyStat::create([
            'visit_date' => $date,
            'locale' => 'en',
            'path' => '/',
            'path_hash' => $pathHash,
            'route_name' => 'root',
            'page_type' => 'page',
            'source_key' => 'search',
            'source_type' => 'search',
            'views' => 305,
            'unique_visitors' => 159,
        ]);

        SiteVisitDailyStat::create([
            'visit_date' => $date,
            'locale' => 'en',
            'path' => '/',
            'path_hash' => $pathHash,
            'route_name' => 'localized.root',
            'page_type' => 'page',
            'source_key' => 'referral',
            'source_type' => 'referral',
            'views' => 260,
            'unique_visitors' => 147,
        ]);

        $method = new ReflectionMethod(SiteAccessAnalytics::class, 'topPages');
        $rows = $method->invoke(new SiteAccessAnalytics, now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->assertCount(1, $rows);
        $this->assertSame('/', $rows->first()->path);
        $this->assertSame('home', $rows->first()->route_name);
        $this->assertSame('home', $rows->first()->page_type);
        $this->assertSame(1236, (int) $rows->first()->views);
        $this->assertSame(922, (int) $rows->first()->visitors);
    }

    public function test_credit_pack_order_grants_credits_once(): void
    {
        $plan = Plan::factory()->create([
            'features' => ['credits' => 10],
            'billing_cycle' => Plan::BILLING_ONE_TIME,
        ]);
        $order = Order::factory()->create([
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
        ]);

        $service = app(StripeService::class);
        $method = (new ReflectionClass($service))->getMethod('grantCreditsForOrder');
        $method->setAccessible(true);

        $method->invoke($service, $order, $plan);
        $method->invoke($service, $order, $plan);

        $this->assertDatabaseHas('credit_grants', [
            'user_id' => $order->user_id,
            'product_code' => $plan->product->code,
            'quantity' => 10,
            'used' => 0,
            'source_type' => 'order',
            'source_id' => (string) $order->id,
        ]);

        $this->assertDatabaseCount('credit_grants', 1);
    }

    public function test_product_admin_resources_load_and_save_homepage_display_settings(): void
    {
        $this->seed();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->assertSame('产品管理', CatalogItemResource::getNavigationGroup());
        $this->assertSame('产品资料', CatalogItemResource::getNavigationLabel());
        $this->assertSame('产品管理', HomepageDisplayResource::getNavigationGroup());
        $this->assertSame('首页展示', HomepageDisplayResource::getNavigationLabel());

        $records = CatalogItem::query()
            ->orderBy('homepage_sort_order')
            ->limit(10)
            ->get();

        Livewire::test(ListHomepageDisplays::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($records);

        $item = CatalogItem::query()->firstOrFail();

        Livewire::test(EditHomepageDisplay::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'show_on_homepage' => true,
                'homepage_sort_order' => 3,
                'is_visible' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('catalog_items', [
            'id' => $item->id,
            'show_on_homepage' => true,
            'homepage_sort_order' => 3,
            'is_visible' => true,
        ]);
    }
}
