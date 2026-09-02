<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id', 'name', 'url', 'category', 'active', 'last_fetched_at',
        'last_success_at', 'failure_count', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'active' => 'boolean',
            'last_fetched_at' => 'datetime',
            'last_success_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function getHealthStatusAttribute(): string
    {
        if ($this->last_error !== null || $this->failure_count > 0) {
            return 'unhealthy';
        }

        return $this->last_success_at !== null ? 'healthy' : 'pending';
    }
}
