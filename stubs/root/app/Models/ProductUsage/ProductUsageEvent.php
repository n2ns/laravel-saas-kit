<?php

namespace App\Models\ProductUsage;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ProductUsageEvent extends Model
{
    /**
     * Default table name (will be overridden by forClient()).
     * This prevents Laravel from inferring a shared table that does not exist.
     */
    protected $table = 'product_usage_events_starter';

    /**
     * Disable default timestamps (we only use created_at).
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',     // UUID for idempotency (from edge gateway)
        'client',       // Client identifier (e.g., starter)
        'user_id',
        'event',        // Legacy field name
        'event_type',   // New field name from edge gateway
        'category',     // Event category (from edge gateway)
        'locale',
        'model',
        'role',
        'prompt_key',
        'prompt_label',
        'tokens_in',
        'tokens_out',
        'properties',
        'context',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'properties' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
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
        $instance->setTable($config['table']);

        return $instance;
    }

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a property value from the properties JSON.
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * Scope: Filter by event type.
     */
    public function scopeOfType($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope: Filter by today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
