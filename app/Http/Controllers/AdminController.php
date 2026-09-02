<?php

namespace App\Http\Controllers;

use App\Models\AiMetric;
use App\Models\Article;
use App\Models\Feed;
use App\Models\NewsCluster;
use App\Services\Admin\AdminOperations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $feeds = Feed::query()
            ->with('source')
            ->withCount('articles')
            ->orderByDesc('failure_count')
            ->orderBy('name')
            ->get();
        $processing = Article::query()
            ->select('processing_status', DB::raw('count(*) as aggregate'))
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status');
        $failedArticles = Article::query()
            ->where('processing_status', Article::STATUS_FAILED)
            ->with('feed')
            ->latest('updated_at')
            ->limit(100)
            ->get();
        $todayClusters = NewsCluster::query()
            ->where('created_at', '>=', today())
            ->with('articles.feed.source')
            ->get();
        $averageSources = $todayClusters->avg(function (NewsCluster $cluster): int {
            return $cluster->articles
                ->map(fn ($article) => $article->feed?->source?->id)
                ->filter()
                ->unique()
                ->count();
        }) ?: 0;
        $aiMetrics = AiMetric::query()->latest('recorded_at')->limit(50)->get();
        $aiTotals = AiMetric::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.observability', [
            'feeds' => $feeds,
            'processing' => $processing,
            'failedArticles' => $failedArticles,
            'todayClusters' => $todayClusters,
            'averageSources' => $averageSources,
            'aiMetrics' => $aiMetrics,
            'aiTotals' => $aiTotals,
        ]);
    }

    public function reprocessArticle(Article $article, AdminOperations $operations): RedirectResponse
    {
        $operations->reprocessArticle($article);

        return back()->with('status', 'Artigo enviado novamente para processamento.');
    }

    public function retryFeed(Feed $feed, AdminOperations $operations): RedirectResponse
    {
        $operations->retryFeed($feed);

        return back()->with('status', 'Feed enviado novamente para a fila.');
    }

    public function recalculateCluster(NewsCluster $newsCluster, AdminOperations $operations): RedirectResponse
    {
        $score = $operations->recalculateCluster($newsCluster);

        return back()->with('status', "NewsCluster recalculado: {$score}/100.");
    }
}
