<?php

namespace App\Filament\Pages;

use App\Models\BlogPost;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUsage\ProductUsageDaily;
use App\Models\Subscription;
use App\Models\User;
use App\Support\LocaleProfile;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use UnitEnum;

class OperationsDashboard extends Page
{
    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Data Center';

    protected static ?string $navigationLabel = 'Operations dashboard';

    protected static ?string $title = 'Operations dashboard';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.operations-dashboard';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'dashboard';
    }

    protected function getViewData(): array
    {
        $today = now()->toDateString();
        $last30Days = now()->subDays(29)->toDateString();

        $publishedPosts = BlogPost::published()->count();
        $draftPosts = BlogPost::where('status', 'draft')->count();
        $missingTranslationCount = $this->missingTranslationCount();

        $paidOrders = Order::where('status', 'paid');
        $monthRevenue = (clone $paidOrders)->where('paid_at', '>=', $last30Days)->sum('total');
        $activeSubscriptions = Subscription::whereIn('stripe_status', [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
        ])->count();

        $productEventsToday = collect(array_keys(config('product_usage.clients', [])))
            ->mapWithKeys(fn (string $clientId): array => [
                $clientId => ProductUsageDaily::forClient($clientId)
                    ->where('date', $today)
                    ->sum('event_count'),
            ]);

        return [
            'contentStats' => [
                'published_posts' => $publishedPosts,
                'draft_posts' => $draftPosts,
                'missing_translation_posts' => $missingTranslationCount,
                'products' => Product::count(),
            ],
            'businessStats' => [
                'users' => User::count(),
                'paid_orders' => Order::where('status', 'paid')->count(),
                'failed_orders' => Order::where('status', 'failed')->count(),
                'month_revenue' => $monthRevenue,
                'active_subscriptions' => $activeSubscriptions,
            ],
            'productEventsToday' => $productEventsToday,
        ];
    }

    private function missingTranslationCount(): int
    {
        $locales = collect(LocaleProfile::supported())
            ->reject(fn (string $locale): bool => $locale === LocaleProfile::default())
            ->values();

        if ($locales->isEmpty()) {
            return 0;
        }

        return BlogPost::published()
            ->where(function ($query) use ($locales): void {
                foreach ($locales as $locale) {
                    $query->orWhere(function ($localeQuery) use ($locale): void {
                        $localeQuery
                            ->whereDoesntHave('translations', fn ($translationQuery) => $translationQuery->where('locale', $locale))
                            ->orWhereHas('translations', function ($translationQuery) use ($locale): void {
                                $translationQuery
                                    ->where('locale', $locale)
                                    ->where(function ($missingQuery): void {
                                        $missingQuery
                                            ->whereNull('title')
                                            ->orWhere('title', '')
                                            ->orWhereNull('content')
                                            ->orWhere('content', '');
                                    });
                            });
                    });
                }
            })
            ->count();
    }
}
