<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider', 'model', 'operation', 'input_tokens', 'output_tokens',
        'duration_ms', 'status', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'duration_ms' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }
}
