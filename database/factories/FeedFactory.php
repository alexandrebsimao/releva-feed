<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Feed> */
class FeedFactory extends Factory
{
    protected $model = Feed::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'name' => fake()->words(3, true),
            'url' => fake()->url().'/feed.xml',
            'category' => fake()->optional()->word(),
            'active' => true,
            'last_fetched_at' => null,
            'last_success_at' => null,
            'failure_count' => 0,
            'last_error' => null,
        ];
    }
}
