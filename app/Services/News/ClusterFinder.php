<?php

namespace App\Services\News;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Services\Topic\TopicNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ClusterFinder
{
    public const WINDOW_HOURS = 72;

    public const THRESHOLD = 0.65;

    public function __construct(private readonly TopicNormalizer $normalizer) {}

    public function find(Article $article): ?NewsCluster
    {
        $publishedAt = $this->publishedAt($article);

        if ($publishedAt === null) {
            return null;
        }

        $article->loadMissing('topics');

        $clusters = NewsCluster::query()
            ->whereBetween('last_article_at', [
                $publishedAt->copy()->subHours(self::WINDOW_HOURS),
                $publishedAt->copy()->addHours(self::WINDOW_HOURS),
            ])
            ->with('articles.topics')
            ->orderByDesc('last_article_at')
            ->get();

        return $clusters
            ->map(fn (NewsCluster $cluster): array => [
                'cluster' => $cluster,
                'score' => $this->similarity($article, $cluster),
            ])
            ->filter(fn (array $match): bool => $match['score'] >= self::THRESHOLD)
            ->sortByDesc('score')
            ->first()['cluster'] ?? null;
    }

    public function similarity(Article $article, NewsCluster $cluster): float
    {
        $article->loadMissing('topics');
        $cluster->loadMissing('articles.topics');

        $bestScore = 0.0;

        foreach ($cluster->articles as $reference) {
            $bestScore = max($bestScore, $this->pairSimilarity($article, $reference));
        }

        return round($bestScore, 4);
    }

    private function pairSimilarity(Article $article, Article $reference): float
    {
        $articleTopicIds = $article->topics->modelKeys();
        $referenceTopicIds = $reference->topics->modelKeys();
        $sharedTopicIds = array_intersect($articleTopicIds, $referenceTopicIds);

        if ($sharedTopicIds === []) {
            return 0.0;
        }

        $titleSimilarity = $this->setSimilarity(
            $this->significantWords($article->original_title),
            $this->significantWords($reference->original_title),
        );
        $textSimilarity = $this->setSimilarity(
            $this->significantWords($article->original_title.' '.$article->description),
            $this->significantWords($reference->original_title.' '.$reference->description),
        );

        // Shared topics alone are not enough: the event must also have textual context in common.
        if ($titleSimilarity < 0.25 && $textSimilarity < 0.20) {
            return 0.0;
        }

        $topicSimilarity = count($sharedTopicIds) / max(count($articleTopicIds), count($referenceTopicIds), 1);
        $entitySimilarity = $this->entitySimilarity($article, $reference);
        $temporalSimilarity = $this->temporalSimilarity($article, $reference);

        return min(1.0, (
            (0.15 * $temporalSimilarity)
            + (0.20 * $topicSimilarity)
            + (0.20 * $entitySimilarity)
            + (0.30 * $titleSimilarity)
            + (0.15 * $textSimilarity)
        ));
    }

    private function entitySimilarity(Article $article, Article $reference): float
    {
        $articleEntities = $article->topics
            ->filter(fn ($topic): bool => $topic->type !== Topic::TYPE_TOPIC)
            ->map(fn ($topic): string => $this->normalizer->normalize($topic->name))
            ->unique()
            ->values();
        $referenceEntities = $reference->topics
            ->filter(fn ($topic): bool => $topic->type !== Topic::TYPE_TOPIC)
            ->map(fn ($topic): string => $this->normalizer->normalize($topic->name))
            ->unique()
            ->values();

        if ($articleEntities->isEmpty() || $referenceEntities->isEmpty()) {
            return 0.0;
        }

        return $articleEntities->intersect($referenceEntities)->count()
            / max($articleEntities->count(), $referenceEntities->count());
    }

    private function temporalSimilarity(Article $article, Article $reference): float
    {
        $articleDate = $this->publishedAt($article);
        $referenceDate = $this->publishedAt($reference);

        if ($articleDate === null || $referenceDate === null) {
            return 0.0;
        }

        $hours = abs($articleDate->diffInMinutes($referenceDate, true)) / 60;

        return max(0.0, 1 - ($hours / self::WINDOW_HOURS));
    }

    /** @param list<string> $left @param list<string> $right */
    private function setSimilarity(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($left, $right));
        $union = count(array_unique([...$left, ...$right]));

        return $union === 0 ? 0.0 : $intersection / $union;
    }

    /** @return list<string> */
    private function significantWords(?string $text): array
    {
        $normalized = $this->normalizer->normalize($text ?? '');
        $stopWords = [
            'a', 'ao', 'aos', 'as', 'com', 'como', 'da', 'das', 'de', 'do', 'dos',
            'e', 'em', 'entre', 'essa', 'esse', 'esta', 'este', 'foi', 'na', 'nas',
            'no', 'nos', 'o', 'os', 'para', 'por', 'que', 'se', 'sem', 'sua', 'suas',
            'seu', 'seus', 'um', 'uma', 'uns', 'umas', 'the', 'an', 'and', 'for',
            'from', 'in', 'of', 'on', 'or', 'that', 'this', 'to', 'with', 'after',
            'before', 'about', 'new', 'news', 'agora', 'anuncia', 'anunciou', 'confirma',
            'confirmou', 'chega', 'chegou', 'diz', 'afirma', 'novo', 'nova', 'novos',
            'novas', 'segundo', 'sobre',
        ];

        return collect(preg_split('/\s+/u', $normalized) ?: [])
            ->filter(fn (string $word): bool => Str::length($word) >= 3 && ! in_array($word, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function publishedAt(Article $article): ?CarbonInterface
    {
        try {
            return $article->published_at instanceof CarbonInterface ? $article->published_at : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
