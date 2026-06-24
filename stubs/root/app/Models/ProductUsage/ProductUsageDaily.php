<?php

namespace App\Models\ProductUsage;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductUsageDaily extends Model
{
    /**
     * Default table name (will be overridden by forClient()).
     * This prevents Laravel from inferring a shared table that does not exist.
     */
    protected $table = 'product_usage_daily_starter';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date',
        'event_count',
        'tokens_in_total',
        'tokens_out_total',
        'event_breakdown',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'event_count' => 'integer',
        'tokens_in_total' => 'integer',
        'tokens_out_total' => 'integer',
        'event_breakdown' => 'array',
    ];

    /**
     * Create a new instance for a specific client.
     */
    public static function forClient(string $clientId): self
    {
        $config = config("product_usage.clients.{$clientId}");

        if (! $config) {
            throw new InvalidArgumentException("Unknown product usage client: {$clientId}");
        }

        $instance = new self;
        $instance->setTable($config['daily_table']);

        return $instance;
    }

    /**
     * Get the user that owns this daily record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create today's record for a user.
     */
    public static function getOrCreateToday(string $clientId, int $userId): self
    {
        $config = config("product_usage.clients.{$clientId}");
        $instance = static::forClient($clientId);

        $defaults = [
            'event_count' => 0,
            'event_breakdown' => [],
        ];

        // Only include token fields if this client tracks them
        if ($config['track_tokens'] ?? false) {
            $defaults['tokens_in_total'] = 0;
            $defaults['tokens_out_total'] = 0;
        }

        return $instance->newQuery()->createOrFirst(
            [
                'user_id' => $userId,
                'date' => today(),
            ],
            $defaults
        );
    }

    /**
     * Increment event count and optionally token counts.
     * Uses database transaction to prevent race conditions.
     */
    public function incrementEvent(string $eventType, int $tokensIn = 0, int $tokensOut = 0): void
    {
        DB::transaction(function () use ($eventType, $tokensIn, $tokensOut) {
            $daily = $this->newQuery()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Increment event count
            $daily->event_count = ($daily->event_count ?? 0) + 1;

            // Update event breakdown atomically
            $breakdown = $daily->event_breakdown ?? [];
            $breakdown[$eventType] = ($breakdown[$eventType] ?? 0) + 1;
            $daily->event_breakdown = $breakdown;

            // Update token counts if applicable
            if ($tokensIn > 0) {
                $daily->tokens_in_total = ($daily->tokens_in_total ?? 0) + $tokensIn;
            }
            if ($tokensOut > 0) {
                $daily->tokens_out_total = ($daily->tokens_out_total ?? 0) + $tokensOut;
            }

            $daily->save();
        });
    }
}
