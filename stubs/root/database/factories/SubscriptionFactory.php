<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'type' => 'default',
            'stripe_id' => 'sub_'.$this->faker->uuid,
            'stripe_status' => Subscription::STATUS_ACTIVE,
            'stripe_price' => 'price_'.$this->faker->uuid,
            'quantity' => 1,
            'trial_ends_at' => null,
            'current_period_ends_at' => null,
            'ends_at' => null,
        ];
    }
}
