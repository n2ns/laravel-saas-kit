<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'gateway_'.$this->faker->unique()->numerify('########'),
            'name' => $this->faker->word,
            'is_active' => true,
            'config' => [],
        ];
    }
}
