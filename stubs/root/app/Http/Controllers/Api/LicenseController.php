<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function show(Request $request, string $product): JsonResponse
    {
        if (! Product::where('code', $product)->exists()) {
            return response()->json(['message' => "Product '{$product}' not found."], 404);
        }

        /** @var User $user */
        $user = $request->user();

        $subscriptions = $user
            ->subscriptions()
            ->with(Subscription::productCatalogRelations())
            ->whereHas('plan.product', fn ($q) => $q->where('code', $product))
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get();

        return response()->json([
            'success' => true,
            'subscriptions' => SubscriptionResource::collection($subscriptions),
        ]);
    }
}
