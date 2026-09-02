<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClusterArticle extends Pivot
{
    public $incrementing = false;

    protected $table = 'cluster_article';

    protected $fillable = ['news_cluster_id', 'article_id', 'similarity_score'];

    protected function casts(): array
    {
        return [
            'news_cluster_id' => 'integer',
            'article_id' => 'integer',
            'similarity_score' => 'float',
        ];
    }
}
