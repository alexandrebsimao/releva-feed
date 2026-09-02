<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'feed_id', 'guid', 'url', 'canonical_url', 'original_title', 'description',
        'author', 'image_url', 'published_at', 'imported_at', 'content_hash',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'feed_id' => 'integer',
            'published_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class)
            ->withPivot(['confidence', 'detected_by'])
            ->withTimestamps();
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(NewsCluster::class, 'cluster_article', 'article_id', 'news_cluster_id')
            ->using(ClusterArticle::class)
            ->withPivot('similarity_score')
            ->withTimestamps();
    }
}
