<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ProductUsageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Persist a product-usage event asynchronously.
 *
 * Tracking is fire-and-forget telemetry. Writing the event row, the daily
 * aggregate row, and the increment inline would put three synchronous writes
 * on every tracked action's request path. Off-loading to the queue keeps the
 * endpoint a thin enqueue.
 */
class TrackProductUsage implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $clientId,
        public int $userId,
        public string $eventId,
        public string $event,
        public array $properties = [],
        public array $context = [],
    ) {}

    public function handle(ProductUsageService $productUsageService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('Product usage event dropped because user no longer exists', [
                'client_id' => $this->clientId,
                'event_id' => $this->eventId,
                'user_id' => $this->userId,
            ]);

            return;
        }

        $productUsageService->track(
            clientId: $this->clientId,
            user: $user,
            eventId: $this->eventId,
            event: $this->event,
            properties: $this->properties,
            context: $this->context,
        );
    }
}
