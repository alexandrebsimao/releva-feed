<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicAlias extends Model
{
    use HasFactory;

    protected $fillable = ['topic_id', 'name', 'normalized_name'];

    protected $casts = ['topic_id' => 'integer'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
