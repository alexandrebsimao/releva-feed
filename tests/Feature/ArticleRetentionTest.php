<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Services\Article\ArticleRetentionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_articles_older_than_fifteen_days_and_orphan_clusters(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        config(['news.article_retention_days' => 15]);

        $old = Article::factory()->create([
            'published_at' => $now->copy()->subDays(16),
        ]);
        $recent = Article::factory()->create([
            'published_at' => $now->copy()->subDays(14),
        ]);
        $oldOnlyCluster = NewsCluster::factory()->create();
        $retainedCluster = NewsCluster::factory()->create();
        $oldOnlyCluster->articles()->attach($old);
        $retainedCluster->articles()->attach([$old->id, $recent->id]);

        $topic = Topic::factory()->create();
        $old->topics()->attach($topic, ['confidence' => 0.9, 'detected_by' => 'test']);

        $report = app(ArticleRetentionService::class)->prune($now);

        $this->assertSame(1, $report['articles_deleted']);
        $this->assertSame(1, $report['clusters_deleted']);
        $this->assertDatabaseMissing('articles', ['id' => $old->id]);
        $this->assertDatabaseHas('articles', ['id' => $recent->id]);
        $this->assertDatabaseMissing('news_clusters', ['id' => $oldOnlyCluster->id]);
        $this->assertDatabaseHas('news_clusters', ['id' => $retainedCluster->id]);
        $this->assertDatabaseMissing('article_topic', ['article_id' => $old->id]);
        $this->assertDatabaseMissing('cluster_article', ['article_id' => $old->id]);
        $this->assertDatabaseHas('cluster_article', [
            'news_cluster_id' => $retainedCluster->id,
            'article_id' => $recent->id,
        ]);
    }

    public function test_article_at_the_retention_boundary_is_kept(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        config(['news.article_retention_days' => 15]);
        $article = Article::factory()->create([
            'published_at' => $now->copy()->subDays(15),
        ]);

        app(ArticleRetentionService::class)->prune($now);

        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }
}
