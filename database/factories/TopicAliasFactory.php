<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\TopicAlias;
use App\Services\Topic\TopicNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TopicAlias> */
class TopicAliasFactory extends Factory
{
    protected $model = TopicAlias::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'topic_id' => Topic::factory(),
            'name' => $name,
            'normalized_name' => app(TopicNormalizer::class)->normalize($name),
        ];
    }
}
