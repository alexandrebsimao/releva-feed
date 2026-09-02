<?php

namespace Tests\Feature;

use App\AI\AiClient;
use App\AI\AiTasks;
use App\AI\FakeAiClient;
use App\AI\NewsSummarizer;
use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Services\News\ClusterBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NewsClusterSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.summarizer_model' => 'summarizer-test-model']);
    }

    public function test_it_summarizes_a_cluster_from_all_articles_and_persists_the_result(): void
    {
        $first = Article::factory()->create([
            'original_title' => 'Toyota anuncia novo SUV híbrido',
            'description' => 'A montadora apresentou o veículo durante um evento no Brasil.',
            'published_at' => now()->subHour(),
        ]);
        $second = Article::factory()->create([
            'original_title' => 'Toyota confirma SUV híbrido para o Brasil',
            'description' => 'A empresa confirmou a chegada do utilitário ao mercado brasileiro.',
            'published_at' => now(),
        ]);
        $first->load('feed.source');
        $second->load('feed.source');
        $first->feed->source->update(['name' => 'Motor1']);
        $second->feed->source->update(['name' => 'AutoPapo']);
        $topic = Topic::factory()->create(['name' => 'Toyota', 'type' => Topic::TYPE_COMPANY]);
        $this->attachTopic($first, $topic);
        $this->attachTopic($second, $topic);

        $cluster = app(ClusterBuilder::class)->build($first);
        app(ClusterBuilder::class)->build($second);
        $fake = $this->fakeFor([
            'title' => 'Toyota confirma SUV híbrido no Brasil',
            'summary' => 'A Toyota confirmou um SUV híbrido para o mercado brasileiro.',
            'why_it_matters' => 'A chegada amplia a oferta de veículos híbridos no país.',
            'importance' => 85,
        ]);

        $result = app(NewsSummarizer::class)->summarizeCluster($cluster->fresh());

        $this->assertSame('Toyota confirma SUV híbrido no Brasil', $result['title']);
        $this->assertDatabaseHas('news_clusters', [
            'id' => $cluster->id,
            'title' => $result['title'],
            'summary' => $result['summary'],
            'why_it_matters' => $result['why_it_matters'],
            'importance_score' => 85,
        ]);
        $this->assertCount(1, $fake->calls());
        $payload = json_decode($fake->calls()[0]['prompt'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Motor1', 'AutoPapo'], array_column($payload['articles'], 'source'));
        $this->assertSame($first->original_title, $payload['articles'][0]['original_title']);
        $this->assertSame($second->description, $payload['articles'][1]['description']);
        $this->assertNotEmpty($payload['articles'][0]['date']);
    }

    public function test_complete_cluster_does_not_call_ai_again(): void
    {
        $cluster = NewsCluster::factory()->create([
            'summary' => 'Resumo factual já persistido.',
            'why_it_matters' => 'Justificativa já persistida.',
            'importance_score' => 70,
        ]);
        $cluster->articles()->attach(Article::factory()->create()->id);
        $fake = $this->fakeFor(null);

        $result = app(NewsSummarizer::class)->summarizeCluster($cluster->fresh());

        $this->assertSame('Resumo factual já persistido.', $result['summary']);
        $this->assertCount(0, $fake->calls());
    }

    public function test_new_article_invalidates_the_existing_cluster_summary(): void
    {
        $topic = Topic::factory()->create(['name' => 'Toyota', 'type' => Topic::TYPE_COMPANY]);
        $first = Article::factory()->create([
            'original_title' => 'Toyota anuncia novo SUV híbrido',
            'description' => 'A Toyota apresentou detalhes do SUV híbrido para o mercado brasileiro.',
            'published_at' => now()->subHour(),
        ]);
        $this->attachTopic($first, $topic);
        $builder = app(ClusterBuilder::class);
        $cluster = $builder->build($first);
        $cluster->update([
            'summary' => 'Resumo anterior.',
            'why_it_matters' => 'Importância anterior.',
            'importance_score' => 60,
        ]);
        $second = Article::factory()->create([
            'original_title' => 'Toyota confirma SUV híbrido para o Brasil',
            'description' => 'A montadora confirmou o lançamento do utilitário híbrido no país.',
            'published_at' => now(),
        ]);
        $this->attachTopic($second, $topic);

        $matched = $builder->build($second);

        $this->assertSame($cluster->id, $matched->id);
        $this->assertNull($matched->fresh()->summary);
        $this->assertNull($matched->fresh()->why_it_matters);
        $this->assertNull($matched->fresh()->importance_score);
    }

    public function test_invalid_summary_output_is_rejected_before_persistence(): void
    {
        $cluster = NewsCluster::factory()->create();
        $cluster->articles()->attach(Article::factory()->create()->id);
        $this->fakeFor([
            'title' => 'Título válido',
            'summary' => 'Resumo válido.',
            'why_it_matters' => 'Justificativa válida.',
            'importance' => 101,
        ]);

        $this->expectException(ValidationException::class);

        app(NewsSummarizer::class)->summarizeCluster($cluster);
    }

    public function test_cluster_page_displays_the_summary_and_every_source_article(): void
    {
        $cluster = NewsCluster::factory()->create([
            'title' => 'Acontecimento consolidado',
            'summary' => 'Resumo consolidado da notícia.',
            'why_it_matters' => 'Impacto relevante para o público.',
            'importance_score' => 80,
        ]);
        $first = Article::factory()->create(['original_title' => 'Matéria da primeira fonte']);
        $second = Article::factory()->create(['original_title' => 'Matéria da segunda fonte']);
        $first->load('feed.source');
        $second->load('feed.source');
        $first->feed->source->update(['name' => 'Fonte Um']);
        $second->feed->source->update(['name' => 'Fonte Dois']);
        $cluster->articles()->attach([$first->id, $second->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('news-clusters.show', $cluster))
            ->assertOk()
            ->assertSee('Resumo consolidado da notícia.')
            ->assertSee('Matéria da primeira fonte')
            ->assertSee('Matéria da segunda fonte')
            ->assertSee('Fonte Um')
            ->assertSee('Fonte Dois');
    }

    /** @param array<string, mixed>|null $response */
    private function fakeFor(?array $response): FakeAiClient
    {
        $fake = new FakeAiClient($response === null ? [] : [AiTasks::NEWS_SUMMARIZER => $response]);
        $this->app->instance(AiClient::class, $fake);

        return $fake;
    }

    private function attachTopic(Article $article, Topic $topic): void
    {
        $article->topics()->attach($topic->id, ['confidence' => 0.95, 'detected_by' => 'test']);
    }
}
