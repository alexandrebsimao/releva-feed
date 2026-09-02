<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicPreference extends Model
{
    use HasFactory;

    public const PREFERENCE_FOLLOW = 'follow';

    public const PREFERENCE_EXCLUDE = 'exclude';

    public const PREFERENCES = [
        self::PREFERENCE_FOLLOW,
        self::PREFERENCE_EXCLUDE,
    ];

    public const PRIORITY_LOW = 1;

    public const PRIORITY_NORMAL = 2;

    public const PRIORITY_HIGH = 3;

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
    ];

    protected $fillable = ['user_id', 'topic_id', 'preference', 'priority'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'topic_id' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
