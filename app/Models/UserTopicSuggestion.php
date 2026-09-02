<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicSuggestion extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'topic_id', 'article_count', 'last_seen_at', 'ignored_at'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'topic_id' => 'integer',
            'article_count' => 'integer',
            'last_seen_at' => 'datetime',
            'ignored_at' => 'datetime',
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
