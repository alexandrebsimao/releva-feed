<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserTopicSuggestion> */
class UserTopicSuggestionFactory extends Factory
{
    protected $model = UserTopicSuggestion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'article_count' => fake()->numberBetween(3, 40),
            'last_seen_at' => now(),
            'ignored_at' => null,
        ];
    }
}
