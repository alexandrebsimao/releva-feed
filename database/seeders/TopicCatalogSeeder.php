<?php

namespace Database\Seeders;

use App\AI\TopicCatalog;
use App\Models\Article;
use App\Models\Topic;
use App\Services\Topic\TopicAliasManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $aliases = app(TopicAliasManager::class);

        DB::transaction(function () use ($aliases): void {
            foreach (config('topics.editorial_categories', []) as $category) {
                $topic = Topic::query()->updateOrCreate(
                    ['slug' => $category['slug']],
                    [
                        'name' => $category['name'],
                        'type' => Topic::TYPE_TOPIC,
                        'status' => Topic::STATUS_APPROVED,
                        'active' => true,
                    ],
                );

                $aliases->sync($topic, implode("\n", $category['aliases']));
            }
        });

        $this->backfillFeedCategories();
    }

    private function backfillFeedCategories(): void
    {
        $feedCategories = collect(config('topics.editorial_categories', []))
            ->flatMap(fn (array $category): array => $category['feed_categories'])
            ->unique()
            ->values()
            ->all();

        if ($feedCategories === []) {
            return;
        }

        $catalog = app(TopicCatalog::class);

        Article::query()
            ->whereHas('feed', fn ($query) => $query->whereIn('category', $feedCategories))
            ->with('feed')
            ->chunkById(100, function ($articles) use ($catalog): void {
                foreach ($articles as $article) {
                    $catalog->attachToArticle($article, []);
                }
            });
    }
}
