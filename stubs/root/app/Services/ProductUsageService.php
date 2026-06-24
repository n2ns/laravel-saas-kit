<?php

namespace App\Services;

use App\Models\ProductUsage\ProductUsageDaily;
use App\Models\ProductUsage\ProductUsageEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProductUsageService
{
    /**
     * Track an event for a user.
     *
     * @param  string  $clientId  Client identifier configured in config/product_usage.php
     * @param  User  $user  The user performing the action
     * @param  string  $event  Event name in snake_case
     * @param  array  $properties  Event-specific properties
     * @param  array  $context  Auto-populated context (locale, platform, etc.)
     */
    public function track(
        string $clientId,
        User $user,
        ?string $eventId,
        string $event,
        array $properties = [],
        array $context = []
    ): ProductUsageEvent {
        // Validate client
        $config = config("product_usage.clients.{$clientId}");
        if (! $config) {
            throw new InvalidArgumentException("Unknown product usage client: {$clientId}");
        }

        return DB::transaction(function () use ($clientId, $config, $context, $event, $eventId, $properties, $user): ProductUsageEvent {
            $eventModel = ProductUsageEvent::forClient($clientId);

            $attributes = [
                'event_id' => $eventId,
                'user_id' => $user->id,
                'event' => $event,
                'properties' => $properties ?: null,
                'context' => $context ?: null,
                'created_at' => now(),
            ];

            if ($eventId) {
                $eventModel = $eventModel->newQuery()->createOrFirst(
                    ['event_id' => $eventId],
                    $attributes
                );

                if (! $eventModel->wasRecentlyCreated) {
                    return $eventModel;
                }
            } else {
                $eventModel->fill($attributes);
                $eventModel->save();
            }

            // Update daily aggregates in the same transaction as the new event.
            $this->updateDailyStats($clientId, $user->id, $event, $properties, $config);

            Log::debug("Product usage: {$clientId}.{$event}", [
                'user_id' => $user->id,
                'properties' => $properties,
            ]);

            return $eventModel;
        });
    }

    /**
     * Update daily statistics.
     */
    protected function updateDailyStats(
        string $clientId,
        int $userId,
        string $event,
        array $properties,
        array $config
    ): void {
        $daily = ProductUsageDaily::getOrCreateToday($clientId, $userId);

        // Extract token counts if this client tracks tokens
        $tokensIn = 0;
        $tokensOut = 0;
        if ($config['track_tokens'] ?? false) {
            $tokensIn = (int) ($properties['tokens_in'] ?? 0);
            $tokensOut = (int) ($properties['tokens_out'] ?? 0);
        }

        $daily->incrementEvent($event, $tokensIn, $tokensOut);
    }

    /**
     * Get today's statistics for a user.
     */
    public function getDailyStats(string $clientId, User $user): array
    {
        $config = config("product_usage.clients.{$clientId}");
        if (! $config) {
            throw new InvalidArgumentException("Unknown product usage client: {$clientId}");
        }

        $daily = ProductUsageDaily::forClient($clientId)
            ->where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if (! $daily) {
            $result = [
                'date' => today()->toDateString(),
                'count' => 0,
                'event_breakdown' => [],
            ];

            // Only include token fields for clients that track tokens
            if ($config['track_tokens'] ?? false) {
                $result['tokens_in_total'] = 0;
                $result['tokens_out_total'] = 0;
            }

            return $result;
        }

        $result = [
            'date' => $daily->date->toDateString(),
            'count' => $daily->event_count,
            'event_breakdown' => $daily->event_breakdown ?? [],
        ];

        // Include token stats if this client tracks them
        if ($config['track_tokens'] ?? false) {
            $result['tokens_in_total'] = $daily->tokens_in_total ?? 0;
            $result['tokens_out_total'] = $daily->tokens_out_total ?? 0;
        }

        return $result;
    }

    /**
     * Get summary statistics for a date range.
     */
    public function getSummary(string $clientId, User $user, string $startDate, string $endDate): array
    {
        $config = config("product_usage.clients.{$clientId}");
        if (! $config) {
            throw new InvalidArgumentException("Unknown product usage client: {$clientId}");
        }

        $dailies = ProductUsageDaily::forClient($clientId)
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalCount = $dailies->sum('event_count');
        $breakdown = [];

        foreach ($dailies as $daily) {
            foreach (($daily->event_breakdown ?? []) as $event => $count) {
                $breakdown[$event] = ($breakdown[$event] ?? 0) + $count;
            }
        }

        $result = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_count' => $totalCount,
            'event_breakdown' => $breakdown,
            'days' => $dailies->count(),
        ];

        if ($config['track_tokens'] ?? false) {
            $result['tokens_in_total'] = $dailies->sum('tokens_in_total');
            $result['tokens_out_total'] = $dailies->sum('tokens_out_total');
        }

        return $result;
    }

    /**
     * Get supported event types for a client.
     */
    public function getEventTypes(string $clientId): array
    {
        $config = config("product_usage.clients.{$clientId}");
        if (! $config) {
            throw new InvalidArgumentException("Unknown product usage client: {$clientId}");
        }

        return array_unique(array_merge(
            $config['event_types'] ?? [],
            config('product_usage.default_event_types', [])
        ));
    }

    /**
     * Check if a client exists.
     */
    public function clientExists(string $clientId): bool
    {
        return config("product_usage.clients.{$clientId}") !== null;
    }
}
