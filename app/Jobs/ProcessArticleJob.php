<?php

namespace App\Jobs;

use App\AI\ArticleClassifier;
use App\AI\NewsSummarizer;
use App\AI\TopicCatalog;
use App\Models\Article;
use App\Models\Topic;
use App\Services\Article\ArticleProcessingPreflight;
use App\Services\News\ClusterBuilder;
use App\Services\News\GeneralScoreCalculator;
use App\Services\News\HomepageNewsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessArticleJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $uniqueFor = 900;

    public function __construct(public readonly int $articleId)
    {
        $this->onQueue('articles');
    }

    public function uniqueId(): string
    {
        return (string) $this->articleId;
    }

    public function backoff(): array
    {
        return [120, 600, 1800];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('ai-article-processing'))
                ->shared()
                ->releaseAfter(15)
                ->expireAfter($this->timeout),
        ];
    }

    public function handle(
        ArticleProcessingPreflight $preflight,
        ArticleClassifier $classifier,
        TopicCatalog $catalog,
        ClusterBuilder $clusterBuilder,
        NewsSummarizer $summarizer,
        GeneralScoreCalculator $generalScore,
        ?HomepageNewsService $homepage = null,
    ): void {
        $article = Article::query()->find($this->articleId);

        if ($article === null) {
            Log::notice('Artigo removido antes do processamento.', ['article_id' => $this->articleId]);

            return;
        }

        $reason = $preflight->reason($article);

        if ($reason !== null) {
            $this->handlePreflightResult($article, $reason);

            return;
        }

        $claimed = Article::query()
            ->whereKey($article->getKey())
            ->whereIn('processing_status', [Article::STATUS_PENDING, Article::STATUS_FAILED])
            ->update(['processing_status' => Article::STATUS_PROCESSING]);

        if ($claimed !== 1) {
            Log::info('Processamento de artigo ignorado por outro worker.', ['article_id' => $article->id]);

            return;
        }

        try {
            $article->loadMissing('feed.source');
            $classification = is_array($article->ai_classification)
                ? $article->ai_classification
                : $classifier->classify(
                    $article->original_title,
                    $article->description,
                    $article->feed->source->name,
                    $article->published_at,
                    Topic::query()->where('active', true)->get(),
                );

            if ($article->ai_classification === null) {
                $article->update([
                    'ai_classification' => $classification,
                    'ai_classified_at' => now(),
                    'ai_last_error' => null,
                ]);
            }

            if (! $classification['is_news']) {
                $article->update([
                    'processing_status' => Article::STATUS_IGNORED,
                    'ai_last_error' => null,
                ]);
                Log::info('Artigo ignorado por não ser notícia.', ['article_id' => $article->id]);

                return;
            }

            $topics = $classification['topics'];
            $catalog->attachToArticle($article, $topics);
            $cluster = $clusterBuilder->build($article);
            $article->update([
                'processing_status' => Article::STATUS_PROCESSED,
                'ai_last_error' => null,
            ]);
        } catch (Throwable $exception) {
            $article->newQuery()->whereKey($article->getKey())->update([
                'processing_status' => Article::STATUS_FAILED,
                'ai_last_error' => $this->safeError($exception),
            ]);
            Log::error('Falha no processamento de artigo; o job será re-tentado.', [
                'article_id' => $article->id,
                'exception' => $exception::class,
                'error' => $this->safeError($exception),
            ]);

            throw $exception;
        }

        $summary = null;

        try {
            $summary = $summarizer->summarizeCluster($cluster);
            $generalScore->calculateAndPersist($cluster->refresh());
        } catch (Throwable $exception) {
            $cluster->newQuery()->whereKey($cluster->getKey())->update([
                'ai_last_error' => $this->safeError($exception),
            ]);
            SummarizeClusterJob::dispatch($cluster->id);

            Log::warning('Artigo processado, mas a sumarização foi adiada.', [
                'article_id' => $article->id,
                'cluster_id' => $cluster->id,
                'exception' => $exception::class,
            ]);
        }

        ($homepage ?? app(HomepageNewsService::class))->invalidate();

        Log::info('Artigo processado por IA.', [
            'article_id' => $article->id,
            'topics_count' => count($topics),
            'importance' => $classification['importance'],
            'language' => $classification['language'],
            'cluster_id' => $cluster->id,
            'cluster_importance' => $summary['importance'] ?? null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Job de artigo esgotou as tentativas.', [
            'article_id' => $this->articleId,
            'exception' => $exception === null ? null : $exception::class,
            'error' => $exception === null ? null : $this->safeError($exception),
        ]);
    }

    private function handlePreflightResult(Article $article, string $reason): void
    {
        if (in_array($reason, ['already_processed', 'already_processing'], true)) {
            Log::info('Artigo não precisa de processamento.', [
                'article_id' => $article->id,
                'reason' => $reason,
            ]);

            return;
        }

        $article->update(['processing_status' => Article::STATUS_IGNORED]);
        Log::info('Artigo ignorado antes da IA.', [
            'article_id' => $article->id,
            'reason' => $reason,
        ]);
    }

    private function safeError(Throwable $exception): string
    {
        $message = trim($exception->getMessage()) ?: $exception::class;
        $message = preg_replace('/(?:Bearer\s+|gsk-|sk-)[^\s,;]+/i', '[redacted]', $message) ?? $message;

        return Str::limit($message, 500, '...');
    }
}
