<?php

namespace Database\Factories;

use App\Models\DailyDigest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DailyDigest> */
class DailyDigestFactory extends Factory
{
    protected $model = DailyDigest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => today(),
            'status' => DailyDigest::STATUS_READY,
            'sent_at' => null,
            'analyzed_count' => 1,
            'related_count' => 1,
            'period_start' => now()->subDay(),
            'period_end' => now(),
        ];
    }
}
