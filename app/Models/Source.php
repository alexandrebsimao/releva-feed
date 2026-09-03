<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'domain', 'priority', 'active'];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_source_preferences')
            ->withTimestamps();
    }

    public function getHealthStatusAttribute(): string
    {
        $feeds = $this->relationLoaded('feeds') ? $this->feeds : $this->feeds()->get();
        $feeds = $feeds->where('active', true);

        if ($feeds->isEmpty()) {
            return 'pending';
        }

        if ($feeds->contains(fn (Feed $feed) => $feed->health_status === 'unhealthy')) {
            return 'unhealthy';
        }

        return $feeds->contains(fn (Feed $feed) => $feed->health_status === 'healthy') ? 'healthy' : 'pending';
    }

    public function getLatestErrorFeedAttribute(): ?Feed
    {
        $feeds = $this->relationLoaded('feeds') ? $this->feeds : $this->feeds()->get();

        return $feeds
            ->filter(fn (Feed $feed) => $feed->last_error !== null)
            ->sortByDesc('last_fetched_at')
            ->first();
    }

}
