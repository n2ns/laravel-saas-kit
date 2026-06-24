<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'product' => $order->plan?->product?->code,
            'plan' => $order->plan?->name,
            'type' => $order->type,
            'status' => $order->status,
            'total' => $order->total,
            'currency' => $order->currency,
            'gateway' => $order->gateway?->code,
            'paid_at' => $order->paid_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }
}
