<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Subscription $subscription */
        $subscription = $this->resource;

        return [
            'id' => $subscription->id,
            'product' => $subscription->plan?->product?->code,
            'product_name' => $subscription->plan?->product?->name,
            'plan' => $subscription->plan?->name,
            'tier' => $subscription->plan?->tier,
            'billing_cycle' => $subscription->plan?->billing_cycle,
            'status' => $subscription->stripe_status,
            'gateway' => 'stripe',
            'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
            'ends_at' => $subscription->ends_at?->toISOString(),
            'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
        ];
    }
}
