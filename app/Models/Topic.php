<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    public const TYPE_TOPIC = 'topic';

    public const TYPE_COMPANY = 'company';

    public const TYPE_LOCATION = 'location';

    public const TYPE_TECHNOLOGY = 'technology';

    public const TYPE_PERSON = 'person';

    public const TYPE_PRODUCT = 'product';

    public const STATUS_SUGGESTED = 'suggested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IGNORED = 'ignored';

    public const TYPES = [
        self::TYPE_TOPIC,
        self::TYPE_COMPANY,
        self::TYPE_LOCATION,
        self::TYPE_TECHNOLOGY,
        self::TYPE_PERSON,
        self::TYPE_PRODUCT,
    ];

    public const STATUSES = [
        self::STATUS_SUGGESTED,
        self::STATUS_APPROVED,
        self::STATUS_IGNORED,
    ];

    protected $fillable = ['name', 'slug', 'type', 'status', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(TopicAlias::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)
            ->withPivot(['confidence', 'detected_by'])
            ->withTimestamps();
    }

    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserTopicPreference::class);
    }

    public function userSuggestions(): HasMany
    {
        return $this->hasMany(UserTopicSuggestion::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_topic_preferences')
            ->withPivot(['preference', 'priority'])
            ->withTimestamps();
    }
}
