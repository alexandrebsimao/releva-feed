<?php

namespace App\Services\News;

use App\Models\Article;
use App\Models\NewsCluster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClusterBuilder
{
    public function __construct(
        private readonly ClusterFinder $finder,
        private readonly GeneralScoreCalculator $generalScore,
    ) {}

    public function build(Article $article): NewsCluster
    {
        return DB::transaction(function () use ($article): NewsCluster {
            $article->loadMissing(['topics', 'feed']);
            $existing = $article->clusters()->first();

            if ($existing !== null) {
                $this->updateDates($existing, $article);

                return $existing->refresh();
            }

            $cluster = $this->finder->find($article);
            $similarity = $cluster === null ? null : $this->finder->similarity($article, $cluster);

            if ($cluster === null) {
                $cluster = NewsCluster::create([
                    'slug' => $this->uniqueSlug($article->original_title),
                    'title' => trim($article->original_title) ?: 'Acontecimento',
                    'category' => $article->feed?->category,
                    'published_at' => $article->published_at,
                    'last_article_at' => $article->published_at,
                    'public_status' => NewsCluster::STATUS_AUTOMATIC,
                ]);
            } else {
                $this->updateDates($cluster, $article);
                $this->invalidateSummary($cluster);
            }

            $cluster->articles()->syncWithoutDetaching([
                $article->getKey() => ['similarity_score' => $similarity],
            ]);
            $this->generalScore->calculateAndPersist($cluster);

            return $cluster->refresh();
        });
    }

    public function buildFor(Article $article): NewsCluster
    {
        return $this->build($article);
    }

    private function updateDates(NewsCluster $cluster, Article $article): void
    {
        $articleDate = $article->published_at;
        $publishedAt = $cluster->published_at->lessThan($articleDate) ? $cluster->published_at : $articleDate;
        $lastArticleAt = $cluster->last_article_at->greaterThan($articleDate) ? $cluster->last_article_at : $articleDate;

        $cluster->update([
            'published_at' => $publishedAt,
            'last_article_at' => $lastArticleAt,
        ]);
    }

    private function invalidateSummary(NewsCluster $cluster): void
    {
        if (! $cluster->summary && ! $cluster->why_it_matters && $cluster->importance_score === null) {
            return;
        }

        $cluster->update([
            'summary' => null,
            'why_it_matters' => null,
            'importance_score' => null,
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'acontecimento';
        $slug = $base;
        $suffix = 2;

        while (NewsCluster::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
