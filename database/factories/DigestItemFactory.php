<?php

namespace Database\Factories;

use App\Models\DailyDigest;
use App\Models\DigestItem;
use App\Models\NewsCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DigestItem> */
class DigestItemFactory extends Factory
{
    protected $model = DigestItem::class;

    public function definition(): array
    {
        return [
            'daily_digest_id' => DailyDigest::factory(),
            'news_cluster_id' => NewsCluster::factory(),
            'score' => fake()->numberBetween(50, 100),
            'position' => 1,
        ];
    }
}
