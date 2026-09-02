<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserTopicPreference> */
class UserTopicPreferenceFactory extends Factory
{
    protected $model = UserTopicPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => UserTopicPreference::PRIORITY_NORMAL,
        ];
    }
}
