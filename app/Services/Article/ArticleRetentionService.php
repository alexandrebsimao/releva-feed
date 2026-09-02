<?php

namespace App\Services\Article;

use App\Models\Article;
use App\Models\NewsCluster;
use Carbon\CarbonInterface;

class ArticleRetentionService
{
    private const CHUNK_SIZE = 500;

    /**
     * @return array{cutoff: CarbonInterface, articles_deleted: int, clusters_deleted: int}
     */
    public function prune(?CarbonInterface $now = null): array
    {
        $cutoff = ($now ?? now())->copy()->subDays(
            max(1, (int) config('news.article_retention_days', 15)),
        );
        $articlesDeleted = 0;

        Article::query()
            ->where('published_at', '<', $cutoff)
            ->chunkById(self::CHUNK_SIZE, function ($articles) use (&$articlesDeleted): void {
                $articlesDeleted += Article::query()
                    ->whereKey($articles->modelKeys())
                    ->delete();
            });

        $clustersDeleted = NewsCluster::query()
            ->whereDoesntHave('articles')
            ->delete();

        return [
            'cutoff' => $cutoff,
            'articles_deleted' => $articlesDeleted,
            'clusters_deleted' => $clustersDeleted,
        ];
    }
}
