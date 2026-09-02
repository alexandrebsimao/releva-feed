<?php

namespace Database\Factories;

use App\Models\AiMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiMetric> */
class AiMetricFactory extends Factory
{
    protected $model = AiMetric::class;

    public function definition(): array
    {
        return [
            'provider' => 'groq',
            'model' => 'test-model',
            'operation' => 'article_classifier',
            'input_tokens' => fake()->numberBetween(1, 1000),
            'output_tokens' => fake()->numberBetween(1, 500),
            'duration_ms' => fake()->numberBetween(10, 3000),
            'status' => 'success',
            'recorded_at' => now(),
        ];
    }
}
