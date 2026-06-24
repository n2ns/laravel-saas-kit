<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway_id' => PaymentGateway::factory(),
            'provider_customer_id' => $this->faker->uuid,
            'billing_email' => $this->faker->email,
            'billing_name' => $this->faker->name,
            'billing_country' => $this->faker->countryCode,
        ];
    }
}
