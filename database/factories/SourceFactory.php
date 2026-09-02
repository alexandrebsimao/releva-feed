<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Source> */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'domain' => fake()->unique()->domainName(),
            'priority' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }
}
