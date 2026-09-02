<?php

namespace App\Services\News;

use App\Models\DailyDigest;
use App\Models\NewsCluster;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DigestBuilder
{
    public function __construct(private readonly UserRelevanceCalculator $relevance) {}

    public function isDue(User $user, ?CarbonInterface $at = null): bool
    {
        $now = $this->asCarbon($at);

        if (! $user->digest_enabled || $user->dailyDigests()
            ->whereDate('date', $now->toDateString())
            ->where('status', DailyDigest::STATUS_SENT)
            ->exists()) {
            return false;
        }

        try {
            $dueAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $now->toDateString().' '.($user->digest_time ?: '08:00:00'),
                $now->getTimezone(),
            );
        } catch (\Throwable) {
            $dueAt = $now->copy()->startOfDay()->addHours(8);
        }

        return $now->greaterThanOrEqualTo($dueAt);
    }

    public function build(User $user, ?CarbonInterface $at = null): DailyDigest
    {
        $now = $this->asCarbon($at);
        $digest = DailyDigest::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'date' => $now->toDateString()],
            ['status' => DailyDigest::STATUS_PENDING],
        );

        if (in_array($digest->status, [DailyDigest::STATUS_SENT, DailyDigest::STATUS_SENDING], true)) {
            return $digest->load('items.newsCluster');
        }

        // A failed send keeps its exact content so a retry never rebuilds a different email.
        if ($digest->status === DailyDigest::STATUS_FAILED && $digest->items()->exists()) {
            return $digest->load('items.newsCluster');
        }

        $periodStart = $this->periodStart($user, $now);
        $clusters = $this->candidateQuery($periodStart, $now)->get();
        $scored = $clusters->map(fn (NewsCluster $cluster): array => [
            'cluster' => $cluster,
            'score' => $this->relevance->calculate($user, $cluster),
        ]);
        $relatedCount = $scored->where('score', '>', 0)->count();
        $items = $scored
            ->filter(fn (array $item): bool => $item['score'] >= $this->minimumScore($user))
            ->sort(function (array $left, array $right): int {
                $score = $right['score'] <=> $left['score'];

                return $score !== 0
                    ? $score
                    : $right['cluster']->last_article_at->getTimestamp() <=> $left['cluster']->last_article_at->getTimestamp();
            })
            ->take($this->maxItems($user))
            ->values();

        return DB::transaction(function () use ($digest, $items, $clusters, $relatedCount, $periodStart, $now): DailyDigest {
            $digest->items()->delete();
            $digest->update([
                'status' => $items->isEmpty() ? DailyDigest::STATUS_EMPTY : DailyDigest::STATUS_READY,
                'sent_at' => null,
                'analyzed_count' => $clusters->count(),
                'related_count' => $relatedCount,
                'period_start' => $periodStart,
                'period_end' => $now,
            ]);

            foreach ($items as $position => $item) {
                $digest->items()->create([
                    'news_cluster_id' => $item['cluster']->getKey(),
                    'score' => $item['score'],
                    'position' => $position + 1,
                ]);
            }

            return $digest->fresh(['items.newsCluster']);
        });
    }

    private function periodStart(User $user, CarbonInterface $now): CarbonInterface
    {
        $lastSentAt = $user->dailyDigests()
            ->whereNotNull('sent_at')
            ->where('sent_at', '<', $now)
            ->latest('sent_at')
            ->value('sent_at');

        return $lastSentAt === null
            ? $now->copy()->subDay()
            : Carbon::parse($lastSentAt);
    }

    /** @return Builder<NewsCluster> */
    private function candidateQuery(CarbonInterface $periodStart, CarbonInterface $periodEnd): Builder
    {
        return NewsCluster::query()
            ->whereIn('public_status', [NewsCluster::STATUS_AUTOMATIC, NewsCluster::STATUS_FEATURED])
            ->where('last_article_at', '>', $periodStart)
            ->where('last_article_at', '<=', $periodEnd)
            ->whereDoesntHave('digests', fn (Builder $query): Builder => $query->where('status', DailyDigest::STATUS_SENT))
            ->with(['articles.feed.source', 'articles.topics']);
    }

    private function minimumScore(User $user): int
    {
        return max(0, min(100, (int) ($user->digest_min_score ?? 50)));
    }

    private function maxItems(User $user): int
    {
        return max(0, min(100, (int) ($user->digest_max_items ?? 10)));
    }

    private function asCarbon(?CarbonInterface $at): Carbon
    {
        return $at === null ? now() : Carbon::instance($at);
    }
}
