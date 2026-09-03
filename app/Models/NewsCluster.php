<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsCluster extends Model
{
    use HasFactory;

    public const STATUS_AUTOMATIC = 'automatic';

    public const STATUS_FEATURED = 'featured';

    public const STATUS_HIDDEN = 'hidden';

    public const PUBLIC_STATUSES = [
        self::STATUS_AUTOMATIC,
        self::STATUS_FEATURED,
        self::STATUS_HIDDEN,
    ];

    protected $fillable = [
        'slug', 'title', 'summary', 'why_it_matters', 'category',
        'importance_score', 'general_score', 'published_at', 'last_article_at',
        'ai_summary_at', 'ai_last_error',
        'public_status',
    ];

    protected function casts(): array
    {
        return [
            'importance_score' => 'integer',
            'general_score' => 'integer',
            'published_at' => 'datetime',
            'last_article_at' => 'datetime',
            'ai_summary_at' => 'datetime',
        ];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'cluster_article', 'news_cluster_id', 'article_id')
            ->using(ClusterArticle::class)
            ->withPivot('similarity_score')
            ->withTimestamps();
    }

    public function digests(): BelongsToMany
    {
        return $this->belongsToMany(DailyDigest::class, 'digest_items', 'news_cluster_id', 'daily_digest_id')
            ->withPivot(['score', 'position'])
            ->withTimestamps();
    }
}
