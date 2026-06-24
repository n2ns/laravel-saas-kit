<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['technical', 'announcement', 'changelog']),
            'status' => 'draft',
            'slug' => $this->faker->slug,
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->sentence,
            'published_at' => null,
        ];
    }
}
