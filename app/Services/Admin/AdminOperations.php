<?php

namespace App\Services\Admin;

use App\Jobs\FetchFeedJob;
use App\Jobs\ProcessArticleJob;
use App\Models\Article;
use App\Models\Feed;
use App\Models\NewsCluster;
use App\Services\News\GeneralScoreCalculator;
use App\Services\News\HomepageNewsService;

class AdminOperations
{
    public function __construct(
        private readonly GeneralScoreCalculator $generalScore,
        private readonly HomepageNewsService $homepage,
    ) {}

    public function reprocessArticle(Article $article): void
    {
        if ($article->processing_status !== Article::STATUS_FAILED) {
            abort(422, 'Somente artigos com falha podem ser reprocessados.');
        }

        $article->update(['processing_status' => Article::STATUS_PENDING]);
        ProcessArticleJob::dispatch($article->id);
    }

    public function retryFeed(Feed $feed): void
    {
        if ($feed->failure_count < 1 && $feed->last_error === null) {
            abort(422, 'Somente feeds com falha podem ser executados novamente.');
        }

        FetchFeedJob::dispatch($feed->id)->onQueue('feeds');
    }

    public function recalculateCluster(NewsCluster $cluster): int
    {
        $score = $this->generalScore->calculateAndPersist($cluster->fresh());
        $this->homepage->invalidate();

        return $score;
    }
}
