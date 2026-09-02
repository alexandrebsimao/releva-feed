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
    public function sections(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes((int) config('homepage.cache_ttl_minutes', 5)),
            fn (): array => [
                'highlights' => $this->highlights(),
                'brasil' => $this->byCategory('brasil'),
                'tecnologia' => $this->byCategory('tecnologia'),
                'automotivo' => $this->byCategory('automotivo'),
                'agora' => $this->recent(),
            ],
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

    /** @return Collection<int, NewsCluster> */
    private function highlights(): Collection
    {
        return $this->eligibleQuery()
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

    /** @return Collection<int, NewsCluster> */
    private function byCategory(string $category): Collection
    {
        $terms = config("homepage.categories.{$category}.terms", [$category]);

        return $this->eligibleQuery()
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

    /** @return Collection<int, NewsCluster> */
    private function recent(): Collection
    {
        return $this->eligibleQuery()
            ->where('last_article_at', '>=', now()->subHours((int) config('homepage.now_hours', 24)))
            ->orderByDesc('last_article_at')
            ->orderByDesc('id')
            ->limit((int) config('homepage.limits.agora', 8))
            ->get();
    }

    /** @return Builder<NewsCluster> */
    private function eligibleQuery(): Builder
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
            ->with(['articles.feed.source', 'articles.topics']);
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
