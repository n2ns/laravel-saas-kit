<?php

namespace App\Http\Controllers;

use App\Models\Product;

class PlanController extends Controller
{
    /**
     * Display pricing/plans for a product.
     */
    public function pricing(string $slug)
    {
        $product = Product::withLocalizedTranslations(app()->getLocale())
            ->with([
                'plans' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                },
            ])
            ->where('code', $slug)
            ->firstOrFail();

        // Check if product subscriptions are paused
        if ($product->isPaused()) {
            return view('products.pricing-unavailable', [
                'product' => $product,
            ]);
        }

        $view = "products.pricing.{$product->code}";
        $viewData = [
            'product' => $product,
            'plans' => $product->plans,
        ];

        if ($product->hasTopLevelRoute() && view()->exists($view)) {
            return view($view, $viewData);
        }

        return view('products.pricing.show', $viewData);
    }
}
