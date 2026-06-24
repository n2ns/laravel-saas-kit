<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductConfigController extends Controller
{
    public function show(Request $request, string $product): JsonResponse
    {
        $locale = $request->query('locale');

        $productModel = Product::query()
            ->where('code', $product)
            ->where('is_active', true)
            ->with(['plans' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'product' => [
                'code' => $productModel->code,
                'pricing_url' => $productModel->pricingUrl(is_string($locale) ? $locale : null, true),
            ],
            'plans' => $productModel->plans->map(fn (Plan $plan): array => [
                'code' => $plan->code,
                'name' => $plan->name,
                'description' => $plan->description,
                'tier' => $plan->tier,
                'billing_cycle' => $plan->billing_cycle,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'features' => $plan->features ?? [],
                'display' => $plan->display_payload ?? [],
            ])->values(),
        ]);
    }
}
