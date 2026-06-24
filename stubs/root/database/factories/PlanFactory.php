<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'code' => $this->faker->unique()->slug,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence,
            'tier' => $this->faker->randomElement([Plan::TIER_FREE, Plan::TIER_PRO, Plan::TIER_ENTERPRISE, Plan::TIER_ADDON]),
            'billing_cycle' => $this->faker->randomElement([Plan::BILLING_MONTHLY, Plan::BILLING_YEARLY, Plan::BILLING_LIFETIME, Plan::BILLING_ONE_TIME]),
            'price' => $this->faker->randomFloat(2, 0, 100),
            'currency' => 'USD',
            'trial_days' => 0,
            'features' => [],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
