<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'feed_id' => Feed::factory(),
            'guid' => fake()->unique()->uuid(),
            'url' => fake()->unique()->url(),
            'canonical_url' => null,
            'original_title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'author' => fake()->name(),
            'image_url' => fake()->optional()->imageUrl(),
            'published_at' => now()->subMinutes(fake()->numberBetween(1, 10000)),
            'imported_at' => now(),
            'content_hash' => fake()->unique()->sha256(),
            'processing_status' => Article::STATUS_PENDING,
        ];
    }
}
