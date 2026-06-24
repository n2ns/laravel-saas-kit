<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->subscriptions()
            ->with(Subscription::productCatalogRelations())
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get();

        return response()->json([
            'success' => true,
            'subscriptions' => SubscriptionResource::collection($subscriptions),
        ]);
    }
}
