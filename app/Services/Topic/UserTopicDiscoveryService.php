<?php

namespace App\Services\Topic;

use App\Models\Article;
use App\Models\Topic;
use App\Models\TopicAlias;
use App\Models\User;
use App\Models\UserTopicPreference;
use App\Models\UserTopicSuggestion;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserTopicDiscoveryService
{
    public function __construct(private readonly TopicNormalizer $normalizer) {}

    /** @return Collection<int, UserTopicSuggestion> */
    public function discover(User $user, ?CarbonInterface $at = null): Collection
    {
        $now = $at ?? now();
        $preferenceIds = $user->topicPreferences()->pluck('topic_id');
        $followedIds = $user->topicPreferences()
            ->where('preference', UserTopicPreference::PREFERENCE_FOLLOW)
            ->pluck('topic_id');

        if ($followedIds->isEmpty()) {
            return collect();
        }

        $recentArticles = Article::query()
            ->where('published_at', '>=', $now->copy()->subDays((int) config('topic_discovery.lookback_days', 30)))
            ->where('published_at', '<=', $now)
            ->whereHas('topics', fn ($query) => $query->whereIn('topics.id', $followedIds))
            ->with('feed')
            ->get();
        $sourceIds = $recentArticles->pluck('feed.source_id')->filter()->unique()->values();

        if ($sourceIds->isEmpty()) {
            return collect();
        }

        $articleIds = Article::query()
            ->where('published_at', '>=', $now->copy()->subDays((int) config('topic_discovery.lookback_days', 30)))
            ->where('published_at', '<=', $now)
            ->whereHas('feed', fn ($query) => $query->whereIn('source_id', $sourceIds))
            ->pluck('id');

        if ($articleIds->isEmpty()) {
            return collect();
        }

        $aliasNames = TopicAlias::query()->get(['topic_id', 'normalized_name']);
        $candidates = Topic::query()
            ->where('active', true)
            ->whereNotIn('id', $preferenceIds)
            ->whereHas('articles', fn ($query) => $query->whereIn('articles.id', $articleIds))
            ->withCount(['articles' => fn ($query) => $query->whereIn('articles.id', $articleIds)])
            ->get()
            ->filter(fn (Topic $topic): bool => $this->isUseful($topic))
            ->filter(fn (Topic $topic): bool => $this->isCanonicalCandidate($topic, $aliasNames))
            ->filter(fn (Topic $topic): bool => $topic->articles_count >= (int) config('topic_discovery.minimum_articles', 3))
            ->sortByDesc('articles_count')
            ->take((int) config('topic_discovery.max_suggestions', 10))
            ->values();

        foreach ($candidates as $topic) {
            UserTopicSuggestion::query()->updateOrCreate(
                ['user_id' => $user->getKey(), 'topic_id' => $topic->getKey()],
                ['article_count' => $topic->articles_count, 'last_seen_at' => $now],
            );
        }

        return $user->topicSuggestions()
            ->whereNull('ignored_at')
            ->whereIn('topic_id', $candidates->modelKeys())
            ->with('topic')
            ->orderByDesc('article_count')
            ->get();
    }

    public function ignore(User $user, UserTopicSuggestion $suggestion): void
    {
        abort_unless($suggestion->user_id === $user->getKey(), 404);
        $suggestion->update(['ignored_at' => now()]);
    }

    private function isUseful(Topic $topic): bool
    {
        $normalized = $this->normalizer->normalize($topic->name);
        $generic = config('topic_discovery.generic_terms', []);

        return Str::length($normalized) >= 3
            && ! preg_match('/^\d+$/', $normalized)
            && ! in_array($normalized, $generic, true);
    }

    /** @param Collection<int, TopicAlias> $aliasNames */
    private function isCanonicalCandidate(Topic $topic, Collection $aliasNames): bool
    {
        $normalized = $this->normalizer->normalize($topic->name);

        return ! $aliasNames->contains(
            fn (TopicAlias $alias): bool => $alias->topic_id !== $topic->getKey()
                && $alias->normalized_name === $normalized,
        );
    }
}
