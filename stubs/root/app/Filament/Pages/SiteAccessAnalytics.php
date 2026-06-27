<?php

namespace App\Filament\Pages;

use App\Models\SiteEventDailyStat;
use App\Models\SiteVisitDailyStat;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class SiteAccessAnalytics extends Page
{
    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Data Center';

    protected static ?string $navigationLabel = 'Access analytics';

    protected static ?string $title = 'Access analytics';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.site-access-analytics';

    public string $dateRange = '7';

    protected function getViewData(): array
    {
        $days = $this->resolvedDateRange();
        $startDate = now()->subDays($days - 1)->toDateString();
        $endDate = now()->toDateString();

        $summary = SiteVisitDailyStat::query()
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(views), 0) as views')
            ->selectRaw('COALESCE(SUM(unique_visitors), 0) as visitors')
            ->selectRaw('COUNT(DISTINCT path_hash) as pages')
            ->first();

        $trendStats = SiteVisitDailyStat::query()
            ->select('visit_date')
            ->selectRaw('SUM(views) as views')
            ->selectRaw('SUM(unique_visitors) as visitors')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('visit_date')
            ->get();

        $trendByDate = $trendStats->keyBy(fn (SiteVisitDailyStat $row): string => $row->visit_date->toDateString());
        $trendRows = collect(range($days - 1, 0))
            ->map(fn (int $offset): string => now()->subDays($offset)->toDateString())
            ->map(function (string $date) use ($trendByDate): array {
                $day = $trendByDate->get($date);

                return [
                    'date' => Carbon::parse($date)->format('m-d'),
                    'views' => (int) ($day?->views ?? 0),
                    'visitors' => (int) ($day?->visitors ?? 0),
                ];
            });

        $topPages = $this->topPages($startDate, $endDate);

        $sources = SiteVisitDailyStat::query()
            ->select('source_type', DB::raw('sum(views) as views'), DB::raw('sum(unique_visitors) as visitors'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('source_type')
            ->orderByDesc('views')
            ->get();

        $referrers = SiteVisitDailyStat::query()
            ->select('referrer_host', DB::raw('sum(views) as views'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('referrer_host')
            ->whereNotIn('referrer_host', ['localhost', '127.0.0.1', '::1'])
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $utmCampaigns = SiteVisitDailyStat::query()
            ->select('utm_source', 'utm_medium', 'utm_campaign', DB::raw('sum(views) as views'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('utm_source')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $locales = SiteVisitDailyStat::query()
            ->select('locale', DB::raw('sum(views) as views'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('locale')
            ->orderByDesc('views')
            ->get();

        $countries = SiteVisitDailyStat::query()
            ->select('country_code', DB::raw('sum(views) as views'), DB::raw('sum(unique_visitors) as visitors'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('views')
            ->limit(12)
            ->get();

        $eventTotal = SiteEventDailyStat::query()
            ->whereBetween('event_date', [$startDate, $endDate])
            ->sum('events');

        $events = SiteEventDailyStat::query()
            ->select('event_name', 'event_type', 'target_url', 'catalog_item_id', DB::raw('sum(events) as events'), DB::raw('sum(unique_visitors) as visitors'))
            ->with('catalogItem.translations')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->groupBy('event_name', 'event_type', 'target_url', 'catalog_item_id')
            ->orderByDesc('events')
            ->limit(12)
            ->get();

        return [
            'summary' => [
                'views' => (int) $summary->views,
                'visitors' => (int) $summary->visitors,
                'pages' => (int) $summary->pages,
                'events' => (int) $eventTotal,
                'days' => $days,
                'range_label' => $this->rangeLabel($days),
            ],
            'trendRows' => $trendRows,
            'topPages' => $topPages,
            'sources' => $sources,
            'referrers' => $referrers,
            'utmCampaigns' => $utmCampaigns,
            'locales' => $locales,
            'countries' => $countries,
            'events' => $events,
        ];
    }

    private function resolvedDateRange(): int
    {
        $days = (int) $this->dateRange;

        return in_array($days, [1, 7, 15, 30, 90], true) ? $days : 7;
    }

    private function rangeLabel(int $days): string
    {
        return $days === 1 ? 'Last 24 hours' : "Last {} days";
    }

    /** @return Collection<int, SiteVisitDailyStat> */
    private function topPages(string $startDate, string $endDate): Collection
    {
        return SiteVisitDailyStat::query()
            ->selectRaw('MIN(path) as path')
            ->selectRaw("
                COALESCE(
                    MAX(CASE WHEN route_name = 'home' THEN route_name END),
                    MAX(CASE WHEN page_type <> 'page' THEN route_name END),
                    MIN(route_name)
                ) as route_name
            ")
            ->selectRaw("
                COALESCE(
                    MAX(CASE WHEN page_type = 'home' THEN page_type END),
                    MAX(CASE WHEN page_type <> 'page' THEN page_type END),
                    MIN(page_type)
                ) as page_type
            ")
            ->selectRaw('MAX(catalog_item_id) as catalog_item_id')
            ->selectRaw('MAX(blog_post_id) as blog_post_id')
            ->selectRaw('SUM(views) as views')
            ->selectRaw('SUM(unique_visitors) as visitors')
            ->with([
                'catalogItem.translations',
                'blogPost:id,title,slug,type',
            ])
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('path_hash')
            ->orderByDesc('views')
            ->limit(12)
            ->get();
    }
}
