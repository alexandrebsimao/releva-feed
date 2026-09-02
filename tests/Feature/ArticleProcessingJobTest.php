<?php

namespace Tests\Feature;

use App\AI\AiClient;
use App\AI\AiTasks;
use App\AI\ArticleClassifier;
use App\AI\FakeAiClient;
use App\AI\NewsSummarizer;
use App\AI\TopicCandidateMerger;
use App\AI\TopicCatalog;
use App\AI\TopicDetector;
use App\Jobs\ProcessArticleJob;
use App\Models\Article;
use App\Models\Topic;
use App\Services\Article\ArticleProcessingPreflight;
use App\Services\News\ClusterBuilder;
use App\Services\News\GeneralScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleProcessingJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.classifier_model' => 'classifier-test-model',
            'ai.summarizer_model' => 'summarizer-test-model',
        ]);
    }

    public function test_new_article_is_classified_detected_associated_and_marked_processed(): void
    {
        $article = Article::factory()->create([
            'original_title' => 'Novo avanço na tecnologia brasileira',
            'description' => 'Uma descrição suficiente sobre a notícia e seus principais impactos.',
        ]);
        $fake = $this->fakeFor([
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => [['name' => 'Tecnologia', 'type' => Topic::TYPE_TECHNOLOGY, 'confidence' => 0.8]],
                'importance' => 85,
                'is_news' => true,
                'language' => 'pt-BR',
            ],
            AiTasks::TOPIC_DETECTOR => [
                'topics' => [['name' => 'Tecnologia', 'type' => Topic::TYPE_TECHNOLOGY, 'confidence' => 0.95]],
            ],
            AiTasks::NEWS_SUMMARIZER => [
                'title' => 'Toyota avança com tecnologia híbrida',
                'summary' => 'A notícia apresenta um avanço em tecnologia híbrida.',
                'why_it_matters' => 'O avanço pode influenciar o mercado automotivo.',
                'importance' => 85,
            ],
        ]);

        $this->runJob($article);

        $this->assertSame(Article::STATUS_PROCESSED, $article->fresh()->processing_status);
        $this->assertDatabaseCount('topics', 1);
        $this->assertDatabaseHas('article_topic', [
            'article_id' => $article->id,
            'detected_by' => 'ai',
            'confidence' => 0.95,
        ]);
        $this->assertDatabaseCount('news_clusters', 1);
        $this->assertDatabaseHas('cluster_article', ['article_id' => $article->id]);
        $this->assertCount(3, $fake->calls());
    }

    public function test_processed_article_is_idempotent_and_does_not_call_ai_again(): void
    {
        $article = Article::factory()->create([
            'original_title' => 'Notícia já processada sobre economia',
            'description' => 'O conteúdo desta notícia já foi analisado anteriormente.',
            'processing_status' => Article::STATUS_PROCESSED,
        ]);
        $fake = $this->fakeFor([]);

        $this->runJob($article);

        $this->assertSame(Article::STATUS_PROCESSED, $article->fresh()->processing_status);
        $this->assertCount(0, $fake->calls());
    }

    public function test_insufficient_and_clearly_incompatible_content_are_ignored_before_ai(): void
    {
        $insufficient = Article::factory()->create(['original_title' => 'Curto', 'description' => null]);
        $english = Article::factory()->create([
            'original_title' => 'The latest news about technology and science',
            'description' => 'This is an article about the latest research from the university and its results.',
        ]);
        $fake = $this->fakeFor([]);

        $this->runJob($insufficient);
        $this->runJob($english);

        $this->assertSame(Article::STATUS_IGNORED, $insufficient->fresh()->processing_status);
        $this->assertSame(Article::STATUS_IGNORED, $english->fresh()->processing_status);
        $this->assertCount(0, $fake->calls());
    }

    public function test_duplicate_and_invalid_date_are_rejected_before_ai(): void
    {
        $existing = Article::factory()->create([
            'original_title' => 'Notícia original sobre ciência brasileira',
            'description' => 'Descrição longa o suficiente para passar pelo filtro de conteúdo.',
        ]);
        $duplicate = new Article([
            'original_title' => 'Outra versão da mesma notícia sobre ciência brasileira',
            'description' => 'Descrição longa o suficiente para ser analisada pelo filtro barato.',
            'published_at' => now(),
            'canonical_url' => $existing->url,
        ]);
        $invalidDate = new Article;
        $invalidDate->setRawAttributes([
            'original_title' => 'Notícia com uma data inválida para processamento',
            'description' => 'Descrição longa o suficiente para passar pelo filtro de conteúdo.',
            'published_at' => 'não é uma data',
        ], true);
        $fake = $this->fakeFor([]);
        $preflight = app(ArticleProcessingPreflight::class);

        $this->assertSame('duplicate', $preflight->reason($duplicate));
        $this->assertSame('invalid_date', $preflight->reason($invalidDate));
        $this->assertCount(0, $fake->calls());
    }

    public function test_failed_processing_is_marked_failed_and_can_be_retried(): void
    {
        $article = Article::factory()->create([
            'original_title' => 'Notícia sobre economia brasileira',
            'description' => 'Uma descrição suficiente para permitir a classificação desta notícia.',
        ]);
        $fake = $this->fakeFor([
            AiTasks::ARTICLE_CLASSIFIER => [
                ['topics' => [], 'importance' => 110, 'is_news' => true, 'language' => 'pt-BR'],
            ],
        ]);

        $failed = false;
        try {
            $this->runJob($article);
        } catch (ValidationException) {
            $failed = true;
        } finally {
            $this->assertSame(Article::STATUS_FAILED, $article->fresh()->processing_status);
        }

        $this->assertTrue($failed);

        $fake->queueStructured(AiTasks::ARTICLE_CLASSIFIER, [
            'topics' => [['name' => 'Economia', 'type' => Topic::TYPE_TOPIC, 'confidence' => 0.9]],
            'importance' => 70,
            'is_news' => true,
            'language' => 'pt-BR',
        ]);
        $fake->queueStructured(AiTasks::TOPIC_DETECTOR, [
            'topics' => [['name' => 'Economia', 'type' => Topic::TYPE_TOPIC, 'confidence' => 0.9]],
        ]);
        $fake->queueStructured(AiTasks::NEWS_SUMMARIZER, [
            'title' => 'Economia brasileira em destaque',
            'summary' => 'A notícia apresenta informações sobre a economia brasileira.',
            'why_it_matters' => 'O tema pode afetar decisões econômicas.',
            'importance' => 70,
        ]);

        $this->runJob($article);

        $this->assertSame(Article::STATUS_PROCESSED, $article->fresh()->processing_status);
    }

    /** @param array<string, array<string, mixed>|list<array<string, mixed>>> $structuredResponses */
    private function fakeFor(array $structuredResponses): FakeAiClient
    {
        $fake = new FakeAiClient($structuredResponses);
        $this->app->instance(AiClient::class, $fake);

        return $fake;
    }

    private function runJob(Article $article): void
    {
        (new ProcessArticleJob($article->id))->handle(
            app(ArticleProcessingPreflight::class),
            app(ArticleClassifier::class),
            app(TopicDetector::class),
            app(TopicCandidateMerger::class),
            app(TopicCatalog::class),
            app(ClusterBuilder::class),
            app(NewsSummarizer::class),
            app(GeneralScoreCalculator::class),
        );
    }
}
