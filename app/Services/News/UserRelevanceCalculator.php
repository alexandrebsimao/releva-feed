<?php

namespace App\Services\News;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UserRelevanceCalculator
{
    public function calculate(User $user, NewsCluster|Article $subject): int
    {
        $articles = $subject instanceof NewsCluster
            ? $subject->loadMissing('articles.feed.source', 'articles.topics')->articles
            : collect([$subject->loadMissing('feed.source', 'topics')]);
        $topics = $this->topics($articles);
        $preferences = UserTopicPreference::query()
            ->where('user_id', $user->getKey())
            ->whereIn('topic_id', $topics->pluck('id')->all())
            ->get();

        if ($preferences->contains('preference', UserTopicPreference::PREFERENCE_EXCLUDE)) {
            return 0;
        }

        $followed = UserTopicPreference::query()
            ->where('user_id', $user->getKey())
            ->where('preference', UserTopicPreference::PREFERENCE_FOLLOW)
            ->get();
        $matchedFollowed = $preferences->where('preference', UserTopicPreference::PREFERENCE_FOLLOW);

        if ($matchedFollowed->isEmpty()) {
            return 0;
        }

        $config = config('scoring.user');
        $cluster = $subject instanceof NewsCluster ? $subject : null;
        $sources = $this->sources($articles);
        $importance = $cluster?->importance_score;
        $date = $cluster?->last_article_at ?? $articles->max('published_at');

        return $this->weightedScore([
            'followed_topics' => min(1, $matchedFollowed->count() / max(1, $followed->count())),
            'preference_priority' => $matchedFollowed->avg(fn (UserTopicPreference $preference): float => $preference->priority / UserTopicPreference::PRIORITY_HIGH),
            'importance' => min(1, max(0, (int) $importance) / 100),
            'recency' => $this->recency($date, $config['recency_half_life_hours']),
            'source_count' => min(1, $sources->count() / max(1, $config['max_sources'])),
            'source_quality' => $this->sourceQuality($sources, $config['max_source_priority']),
        ], $config['weights']);
    }

    /** @param Collection<int, Article> $articles @return Collection<int, Topic> */
    private function topics(Collection $articles): Collection
    {
        return $articles
            ->flatMap(fn (Article $article): Collection => $article->topics)
            ->unique(fn (Topic $topic): int => (int) $topic->getKey())
            ->values();
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
    private function sourceQuality(Collection $sources, int|float $maximum): float
    {
        if ($sources->isEmpty()) {
            return 0.0;
        }

        return min(1, $sources->avg(fn ($source): float => max(0, (float) $source->priority) / max(1, $maximum)));
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
