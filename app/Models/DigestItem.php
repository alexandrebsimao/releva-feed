<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigestItem extends Model
{
    use HasFactory;

    protected $fillable = ['daily_digest_id', 'news_cluster_id', 'score', 'position'];

    protected function casts(): array
    {
        return [
            'daily_digest_id' => 'integer',
            'news_cluster_id' => 'integer',
            'score' => 'integer',
            'position' => 'integer',
        ];
    }

    public function digest(): BelongsTo
    {
        return $this->belongsTo(DailyDigest::class, 'daily_digest_id');
    }

    public function newsCluster(): BelongsTo
    {
        return $this->belongsTo(NewsCluster::class);
    }
}
