<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'digest_enabled', 'digest_time',
        'digest_min_score', 'digest_max_items', 'is_admin',
    ];

    public function topicPreferences(): HasMany
    {
        return $this->hasMany(UserTopicPreference::class);
    }

    public function dailyDigests(): HasMany
    {
        return $this->hasMany(DailyDigest::class);
    }

    public function topicSuggestions(): HasMany
    {
        return $this->hasMany(UserTopicSuggestion::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'user_topic_preferences')
            ->withPivot(['preference', 'priority'])
            ->withTimestamps();
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'user_source_preferences')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'digest_enabled' => 'boolean',
            'digest_min_score' => 'integer',
            'digest_max_items' => 'integer',
            'is_admin' => 'boolean',
        ];
    }
}
