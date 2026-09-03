<?php

namespace Tests\Feature;

use App\Jobs\FetchFeedJob;
use App\Jobs\ProcessArticleJob;
use App\Models\AiMetric;
use App\Models\Article;
use App\Models\Feed;
use App\Models\NewsCluster;
use App\Models\Source;
use App\Models\User;
use App\Services\AI\AiMetricRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_observability_requires_authentication_and_admin_authorization(): void
    {
        $this->get(route('admin.observability'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('admin.observability'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.sources.index'))->assertForbidden();
    }

    public function test_admin_can_view_and_toggle_source_catalog_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $source = Source::factory()->create(['name' => 'Fonte administrada', 'active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.sources.index'))
            ->assertOk()
            ->assertSee('Fonte administrada')
            ->assertSee('Desativar fonte');

        $this->actingAs($admin)
            ->patch(route('admin.sources.toggle', $source))
            ->assertRedirect();

        $this->assertDatabaseHas('sources', ['id' => $source->id, 'active' => false]);
    }

    public function test_admin_sees_feed_queue_cluster_and_ai_health_data_without_sensitive_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $feed = Feed::factory()->create([
            'failure_count' => 2,
            'last_fetched_at' => now()->subHour(),
            'last_success_at' => now()->subHours(2),
        ]);
        Article::factory()->create(['feed_id' => $feed->id, 'processing_status' => Article::STATUS_PENDING]);
        Article::factory()->create(['feed_id' => $feed->id, 'processing_status' => Article::STATUS_PROCESSING]);
        Article::factory()->create(['feed_id' => $feed->id, 'processing_status' => Article::STATUS_PROCESSED]);
        Article::factory()->create(['feed_id' => $feed->id, 'processing_status' => Article::STATUS_FAILED]);
        $cluster = NewsCluster::factory()->create(['created_at' => now(), 'last_article_at' => now()]);
        $cluster->articles()->attach(Article::factory()->create(['feed_id' => $feed->id])->id);
        AiMetric::factory()->create([
            'provider' => 'groq',
            'model' => 'llama-test',
            'operation' => 'article_classifier',
            'input_tokens' => 120,
            'output_tokens' => 40,
            'duration_ms' => 230,
            'status' => 'success',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.observability'))
            ->assertOk()
            ->assertSee($feed->name)
            ->assertSee('2')
            ->assertSee('Pendentes')
            ->assertSee('Processando')
            ->assertSee('Clusters criados hoje')
            ->assertSee('llama-test')
            ->assertSee('120 / 40')
            ->assertSee('230 ms')
            ->assertDontSee('GROQ_API_KEY')
            ->assertDontSee('gsk-');
    }

    public function test_admin_can_retry_failed_article_and_feed_and_recalculate_cluster(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $article = Article::factory()->create(['processing_status' => Article::STATUS_FAILED]);
        $feed = Feed::factory()->create(['failure_count' => 1, 'last_error' => 'timeout']);
        $cluster = NewsCluster::factory()->create(['importance_score' => 90]);
        $cluster->articles()->attach($article->id);

        $this->actingAs($admin)->post(route('admin.articles.reprocess', $article))->assertRedirect();
        $this->assertSame(Article::STATUS_PENDING, $article->fresh()->processing_status);
        Queue::assertPushed(ProcessArticleJob::class, fn (ProcessArticleJob $job): bool => $job->articleId === $article->id);

        $this->actingAs($admin)->post(route('admin.feeds.retry', $feed))->assertRedirect();
        Queue::assertPushed(FetchFeedJob::class, fn (FetchFeedJob $job): bool => $job->feedId === $feed->id);

        $this->actingAs($admin)->post(route('admin.news-clusters.recalculate', $cluster))->assertRedirect();
        $this->assertNotNull($cluster->fresh()->general_score);
    }

    public function test_ai_metric_recorder_persists_only_operational_metadata(): void
    {
        app(AiMetricRecorder::class)->record('groq', 'model-test', 'summarizer', 10, 20, 35, 'success');

        $this->assertDatabaseHas('ai_metrics', [
            'provider' => 'groq',
            'model' => 'model-test',
            'operation' => 'summarizer',
            'input_tokens' => 10,
            'output_tokens' => 20,
            'duration_ms' => 35,
            'status' => 'success',
        ]);
        $this->assertFalse(in_array('api_key', (new AiMetric)->getFillable(), true));
    }
}
