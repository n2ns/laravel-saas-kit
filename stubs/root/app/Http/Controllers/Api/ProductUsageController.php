<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TrackProductUsage;
use App\Services\ProductUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductUsageController extends Controller
{
    public function __construct(
        protected ProductUsageService $productUsageService
    ) {}

    public function track(Request $request, string $client): JsonResponse
    {
        if ($error = $this->rejectUnknownClient($client)) {
            return $error;
        }

        $validated = $request->validate([
            'event' => 'required|string|max:50',
            'properties' => 'nullable|array',
            'context' => 'nullable|array',
            'timestamp' => 'nullable|date',
            'event_id' => 'nullable|uuid',
        ]);

        $allowedEvents = $this->productUsageService->getEventTypes($client);
        if (! in_array($validated['event'], $allowedEvents)) {
            Log::warning("Product usage: Unknown event type '{$validated['event']}' for client '{$client}'");
        }

        $eventId = $validated['event_id'] ?? (string) Str::uuid();

        TrackProductUsage::dispatch(
            clientId: $client,
            userId: (int) $request->user()->id,
            eventId: $eventId,
            event: $validated['event'],
            properties: $validated['properties'] ?? [],
            context: $validated['context'] ?? $this->extractContext($request),
        );

        return response()->json([
            'success' => true,
            'event_id' => $eventId,
        ], 202);
    }

    public function daily(Request $request, string $client): JsonResponse
    {
        if ($error = $this->rejectUnknownClient($client)) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'data' => $this->productUsageService->getDailyStats($client, $request->user()),
        ]);
    }

    public function summary(Request $request, string $client): JsonResponse
    {
        if ($error = $this->rejectUnknownClient($client)) {
            return $error;
        }

        $validated = $request->validate([
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date|after_or_equal:start',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->productUsageService->getSummary(
                $client,
                $request->user(),
                $validated['start'],
                $validated['end']
            ),
        ]);
    }

    public function eventTypes(Request $request, string $client): JsonResponse
    {
        if ($error = $this->rejectUnknownClient($client)) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'client' => $client,
            'event_types' => $this->productUsageService->getEventTypes($client),
        ]);
    }

    private function rejectUnknownClient(string $client): ?JsonResponse
    {
        if (! $this->productUsageService->clientExists($client)) {
            return response()->json([
                'success' => false,
                'error' => 'UNKNOWN_CLIENT',
                'message' => "Product usage client '{$client}' is not configured.",
            ], 400);
        }

        return null;
    }

    private function extractContext(Request $request): array
    {
        return [
            'ip_hash' => $this->anonymizeIp($request->ip()),
            'user_agent' => $request->userAgent(),
            'locale' => $request->header('Accept-Language'),
            'platform' => $request->header('X-Platform'),
            'version' => $request->header('X-App-Version'),
        ];
    }

    private function anonymizeIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        $salt = config('app.key').today()->toDateString();

        return substr(hash('sha256', $ip.$salt), 0, 16);
    }
}
