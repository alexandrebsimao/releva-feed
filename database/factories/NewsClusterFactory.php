<?php

namespace Database\Factories;

use App\Models\NewsCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NewsCluster> */
class NewsClusterFactory extends Factory
{
    protected $model = NewsCluster::class;

    public function definition(): array
    {
        $publishedAt = now()->subMinutes(fake()->numberBetween(1, 10000));

        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(),
            'summary' => null,
            'why_it_matters' => null,
            'category' => fake()->optional()->word(),
            'importance_score' => null,
            'general_score' => null,
            'published_at' => $publishedAt,
            'last_article_at' => $publishedAt,
            'public_status' => NewsCluster::STATUS_AUTOMATIC,
        ];
    }
}
