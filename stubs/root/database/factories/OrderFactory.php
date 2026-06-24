<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'gateway_id' => PaymentGateway::factory(),
            'order_number' => $this->faker->unique()->numerify('ORD-####'),
            'type' => Order::TYPE_NEW,
            'status' => Order::STATUS_PAID,
            'subtotal' => 10.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total' => 10.00,
            'currency' => 'USD',
            'paid_at' => now(),
        ];
    }
}
