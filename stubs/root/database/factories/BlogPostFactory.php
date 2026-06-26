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
            'type' => $this->faker->randomElement(BlogPost::typeCodes()),
            'status' => 'draft',
            'is_pinned' => false,
            'pin_order' => 0,
            'pinned_until' => null,
            'slug' => $this->faker->slug,
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->sentence,
            'geo_tags' => null,
            'topics' => null,
            'seo_keywords' => null,
            'related_slugs' => null,
            'published_at' => null,
        ];
    }
}
