<?php

namespace App\Services\News;

use App\Models\NewsCluster;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomepageNewsService
{
    private const CACHE_KEY = 'homepage.news-clusters.v1';

    /**
     * @return array{highlights: Collection<int, NewsCluster>, brasil: Collection<int, NewsCluster>, tecnologia: Collection<int, NewsCluster>, automotivo: Collection<int, NewsCluster>, agora: Collection<int, NewsCluster>}
     */
    /** @param list<int> $sourceIds */
    public function sections(array $sourceIds = []): array
    {
        $sourceIds = collect($sourceIds)->map(fn ($id): int => (int) $id)->filter()->unique()->sort()->values()->all();

        if ($sourceIds !== []) {
            return $this->buildSections($sourceIds);
        }

        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes((int) config('homepage.cache_ttl_minutes', 5)),
            fn (): array => $this->buildSections($sourceIds),
        );
    }

    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{highlights: Collection<int, NewsCluster>, brasil: Collection<int, NewsCluster>, tecnologia: Collection<int, NewsCluster>, automotivo: Collection<int, NewsCluster>, agora: Collection<int, NewsCluster>} */
    public function refresh(): array
    {
        $this->invalidate();

        return $this->sections();
    }

    /** @param list<int> $sourceIds */
    private function buildSections(array $sourceIds): array
    {
        return [
            'highlights' => $this->highlights($sourceIds),
            'brasil' => $this->byCategory('brasil', $sourceIds),
            'tecnologia' => $this->byCategory('tecnologia', $sourceIds),
            'automotivo' => $this->byCategory('automotivo', $sourceIds),
            'agora' => $this->recent($sourceIds),
        ];
    }

    /** @param list<int> $sourceIds @return Collection<int, NewsCluster> */
    private function highlights(array $sourceIds): Collection
    {
        return $this->eligibleQuery($sourceIds)
            ->where(function (Builder $query): void {
                $query->where('public_status', NewsCluster::STATUS_FEATURED)
                    ->orWhere(function (Builder $automatic): void {
                        $automatic
                            ->where('public_status', NewsCluster::STATUS_AUTOMATIC)
                            ->where('general_score', '>=', $this->automaticMinimumScore());
                    });
            })
            ->orderByDesc('public_status')
            ->orderByDesc('general_score')
            ->orderByDesc('last_article_at')
            ->limit((int) config('homepage.limits.highlights', 5))
            ->get();
    }

    /** @param list<int> $sourceIds @return Collection<int, NewsCluster> */
    private function byCategory(string $category, array $sourceIds): Collection
    {
        $terms = config("homepage.categories.{$category}.terms", [$category]);

        return $this->eligibleQuery($sourceIds)
            ->where(function (Builder $query) use ($terms, $category): void {
                $this->whereTextMatches($query, 'category', $terms);
                $query->orWhereHas('articles.feed', function (Builder $feedQuery) use ($terms): void {
                    $this->whereTextMatches($feedQuery, 'category', $terms);
                });

                if ($category === 'tecnologia') {
                    $query->orWhereHas('articles.topics', fn (Builder $topicQuery): Builder => $topicQuery->where('type', Topic::TYPE_TECHNOLOGY));
                }

                $topicTerms = config("homepage.categories.{$category}.topic_terms", $terms);
                $query->orWhereHas('articles.topics', function (Builder $topicQuery) use ($topicTerms): void {
                    $this->whereTextMatches($topicQuery, 'name', $topicTerms);
                });
            })
            ->orderByDesc('general_score')
            ->orderByDesc('last_article_at')
            ->limit((int) config("homepage.limits.{$category}", 4))
            ->get();
    }

    /** @param list<int> $sourceIds @return Collection<int, NewsCluster> */
    private function recent(array $sourceIds): Collection
    {
        return $this->eligibleQuery($sourceIds)
            ->where('last_article_at', '>=', now()->subHours((int) config('homepage.now_hours', 24)))
            ->orderByDesc('last_article_at')
            ->orderByDesc('id')
            ->limit((int) config('homepage.limits.agora', 8))
            ->get();
    }

    /** @param list<int> $sourceIds @return Builder<NewsCluster> */
    private function eligibleQuery(array $sourceIds): Builder
    {
        return NewsCluster::query()
            ->where(function (Builder $query): void {
                $query->where('public_status', NewsCluster::STATUS_FEATURED)
                    ->orWhere(function (Builder $automatic): void {
                        $automatic
                            ->where('public_status', NewsCluster::STATUS_AUTOMATIC)
                            ->where('general_score', '>=', $this->automaticMinimumScore());
                    });
            })
            ->when($sourceIds !== [], fn (Builder $query): Builder => $query->whereHas(
                'articles.feed',
                fn (Builder $feedQuery): Builder => $feedQuery->whereIn('source_id', $sourceIds),
            ))
            ->with([
                'articles' => fn ($articleQuery) => $articleQuery
                    ->when(
                        $sourceIds !== [],
                        fn ($query) => $query->whereHas(
                            'feed',
                            fn (Builder $feedQuery): Builder => $feedQuery->whereIn('source_id', $sourceIds),
                        ),
                    )
                    ->with(['feed.source', 'topics']),
            ]);
    }

    /** @param list<string> $terms */
    private function whereTextMatches(Builder $query, string $column, array $terms): void
    {
        foreach ($terms as $term) {
            $query->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($term, 'UTF-8').'%']);
        }
    }

    private function automaticMinimumScore(): int
    {
        return (int) config('homepage.automatic_min_score', 50);
    }
}
