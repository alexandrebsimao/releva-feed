<?php

namespace App\Services\News;

use App\Models\Article;
use App\Models\NewsCluster;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GeneralScoreCalculator
{
    public function calculate(NewsCluster $cluster): int
    {
        $cluster->loadMissing('articles.feed.source');
        $articles = $cluster->articles;
        $sources = $this->sources($articles);
        $config = config('scoring.general');

        return $this->weightedScore([
            'importance' => $this->percentage($cluster->importance_score),
            'recency' => $this->recency($cluster->last_article_at, $config['recency_half_life_hours']),
            'source_count' => min(1, $sources->count() / max(1, $config['max_sources'])),
            'source_priority' => $this->sourcePriority($sources, $config['max_source_priority']),
            'novelty' => 1 / sqrt(max(1, $sources->count())),
        ], $config['weights']);
    }

    public function calculateAndPersist(NewsCluster $cluster): int
    {
        $score = $this->calculate($cluster);
        $cluster->update(['general_score' => $score]);

        return $score;
    }

    /** @param Collection<int, Article> $articles @return Collection<int, mixed> */
    private function sources(Collection $articles): Collection
    {
        return $articles
            ->filter(fn (Article $article): bool => $article->feed?->source !== null)
            ->map(fn (Article $article) => $article->feed->source)
            ->unique(fn ($source): int => (int) $source->getKey())
            ->values();
    }

    /** @param Collection<int, mixed> $sources */
    private function sourcePriority(Collection $sources, int|float $maximum): float
    {
        if ($sources->isEmpty()) {
            return 0.0;
        }

        return min(1, $sources->avg(fn ($source): float => max(0, (float) $source->priority) / max(1, $maximum)));
    }

    private function percentage(?int $value): float
    {
        return min(1, max(0, (int) $value) / 100);
    }

    private function recency(?CarbonInterface $date, int|float $halfLifeHours): float
    {
        if ($date === null) {
            return 0.0;
        }

        $ageInHours = $date->isFuture() ? 0 : $date->diffInMinutes(now()) / 60;

        return exp(-$ageInHours / max(1, $halfLifeHours));
    }

    /** @param array<string, float> $components @param array<string, float> $weights */
    private function weightedScore(array $components, array $weights): int
    {
        $weightedTotal = 0.0;
        $weightTotal = 0.0;

        foreach ($weights as $name => $weight) {
            $weightedTotal += ($components[$name] ?? 0.0) * $weight;
            $weightTotal += $weight;
        }

        return (int) round($weightTotal > 0 ? ($weightedTotal / $weightTotal) * 100 : 0);
    }
}
