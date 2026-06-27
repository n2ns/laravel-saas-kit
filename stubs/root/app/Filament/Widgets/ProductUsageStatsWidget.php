<?php

namespace App\Filament\Widgets;

use App\Models\ProductUsage\ProductUsageDaily;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductUsageStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $clients = config('product_usage.clients', []);

        // Early return if no clients configured (defensive programming)
        if (empty($clients)) {
            return [];
        }

        $stats = [];
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        foreach (array_keys($clients) as $clientId) {
            $config = $clients[$clientId] ?? [];
            $aggregate = ProductUsageDaily::forClient($clientId)
                ->where('date', '>=', $weekStart)
                ->selectRaw('COALESCE(SUM(CASE WHEN date = ? THEN event_count ELSE 0 END), 0) as today_events', [$today])
                ->selectRaw('COALESCE(SUM(event_count), 0) as week_events')
                ->selectRaw('COUNT(DISTINCT user_id) as week_users')
                ->selectRaw('COALESCE(SUM(tokens_in_total), 0) as tokens_in')
                ->selectRaw('COALESCE(SUM(tokens_out_total), 0) as tokens_out')
                ->first();

            $todayEvents = (int) $aggregate->today_events;
            $weekEvents = (int) $aggregate->week_events;
            $weekUsers = (int) $aggregate->week_users;

            $description = "This week: {} events, {} users";

            if (($config['track_tokens'] ?? false) === true) {
                $description .= ' | Tokens: '.number_format((int) $aggregate->tokens_in + (int) $aggregate->tokens_out);
            }

            $stats[] = Stat::make(strtoupper($clientId).' today', number_format($todayEvents))
                ->description($description)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getColorForClient($clientId));
        }

        return $stats;
    }

    protected function getColorForClient(string $clientId): string
    {
        return match ($clientId) {
            'starter' => 'primary',
            default => 'gray',
        };
    }
}
