<?php

namespace Tests\Feature;

use App\AI\AiClient;
use App\AI\AiTasks;
use App\AI\ArticleClassifier;
use App\AI\FakeAiClient;
use App\AI\NewsSummarizer;
use App\AI\RelevanceAnalyzer;
use App\AI\TopicDetector;
use App\Models\Article;
use App\Models\Topic;
use Database\Seeders\TopicCatalogSeeder;
use App\Services\Topic\TopicNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AIIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.provider' => 'groq',
            'ai.classifier_model' => 'classifier-test-model',
            'ai.summarizer_model' => 'summarizer-test-model',
        ]);
    }

    public function test_article_classifier_returns_a_validated_structured_result_without_calling_groq(): void
    {
        $fake = new FakeAiClient([
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => [['name' => 'Tecnologia', 'type' => Topic::TYPE_TECHNOLOGY, 'confidence' => 0.95]],
                'importance' => 85,
                'is_news' => true,
                'language' => 'pt-BR',
            ],
        ]);
        $this->app->instance(AiClient::class, $fake);

        $result = app(ArticleClassifier::class)->classify(
            'Novo avanço em inteligência artificial',
            'Descrição da notícia.',
            'Fonte Exemplo',
            '2026-09-02T12:00:00-03:00',
            ['Tecnologia', 'Ciência'],
        );

        $this->assertSame(85, $result['importance']);
        $this->assertTrue($result['is_news']);
        $this->assertSame('pt-BR', $result['language']);
        $fake->assertCalled(AiTasks::ARTICLE_CLASSIFIER, fn (array $call): bool => $call['model'] === 'classifier-test-model');
    }

    public function test_invalid_classifier_output_is_rejected_before_topics_are_persisted(): void
    {
        $fake = new FakeAiClient([
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => [['name' => 'Tecnologia', 'type' => Topic::TYPE_TECHNOLOGY, 'confidence' => 1.4]],
                'is_news' => true,
                'language' => 'pt-BR',
            ],
        ]);
        $this->app->instance(AiClient::class, $fake);
        $article = Article::factory()->create();

        $this->expectException(ValidationException::class);

        app(ArticleClassifier::class)->classifyAndPersist($article);
    }

    public function test_classifier_reuses_an_existing_topic_found_through_a_normalized_alias(): void
    {
        $existing = Topic::factory()->create([
            'name' => 'Inteligência Artificial',
            'slug' => 'inteligencia-artificial',
        ]);
        $existing->aliases()->create(['name' => 'IA', 'normalized_name' => 'ia']);
        $fake = new FakeAiClient([
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => [['name' => 'IA', 'type' => Topic::TYPE_TECHNOLOGY, 'confidence' => 0.96]],
                'importance' => 80,
                'is_news' => true,
                'language' => 'pt-BR',
            ],
        ]);
        $this->app->instance(AiClient::class, $fake);
        $article = Article::factory()->create();

        $result = app(ArticleClassifier::class)->classifyAndPersist($article);

        $this->assertSame(1, Topic::query()->count());
        $this->assertDatabaseHas('article_topic', [
            'article_id' => $article->id,
            'topic_id' => $existing->id,
            'detected_by' => 'ai',
            'confidence' => 0.96,
        ]);
        $this->assertSame(80, $result['importance']);
    }

    public function test_all_ai_components_use_the_fake_client_and_validate_their_outputs(): void
    {
        $fake = new FakeAiClient([
            AiTasks::TOPIC_DETECTOR => [
                'topics' => [['name' => 'Economia', 'type' => Topic::TYPE_TOPIC, 'confidence' => 0.9]],
            ],
            AiTasks::RELEVANCE_ANALYZER => [
                'is_relevant' => true,
                'score' => 91,
                'reason' => 'O texto trata diretamente do assunto.',
            ],
            AiTasks::NEWS_SUMMARIZER => [
                'title' => 'Título consolidado',
                'summary' => 'Resumo curto e factual.',
                'why_it_matters' => 'O assunto merece atenção.',
                'importance' => 82,
            ],
        ]);
        $this->app->instance(AiClient::class, $fake);

        $detected = app(TopicDetector::class)->detect('Notícia sobre economia.', ['Economia']);
        $article = Article::factory()->create([
            'original_title' => 'Título',
            'description' => 'Descrição',
        ]);
        $summary = app(NewsSummarizer::class)->summarize([$article]);
        $relevance = app(RelevanceAnalyzer::class)->analyze('Título', 'Descrição', ['Economia']);

        $this->assertSame('Economia', $detected['topics'][0]['name']);
        $this->assertSame('Resumo curto e factual.', $summary['summary']);
        $this->assertSame(91, $relevance['score']);
        $this->assertCount(3, $fake->calls());
        $fake->assertCalled(AiTasks::TOPIC_DETECTOR);
        $fake->assertCalled(AiTasks::NEWS_SUMMARIZER, fn (array $call): bool => $call['model'] === 'summarizer-test-model');
        $fake->assertCalled(AiTasks::RELEVANCE_ANALYZER);
    }

    public function test_topic_normalizer_is_used_by_the_topic_catalog(): void
    {
        $normalizer = app(TopicNormalizer::class);

        $this->assertSame('inteligencia artificial', $normalizer->normalize(' Inteligência-Artificial '));
    }

    public function test_topic_catalog_seeds_broad_editorial_categories_idempotently(): void
    {
        $article = Article::factory()->create();
        $article->feed->update(['category' => 'economy']);

        $this->seed(TopicCatalogSeeder::class);
        $this->seed(TopicCatalogSeeder::class);

        $this->assertDatabaseHas('topics', [
            'name' => 'Economia',
            'slug' => 'economia',
            'type' => Topic::TYPE_TOPIC,
            'status' => Topic::STATUS_APPROVED,
            'active' => true,
        ]);
        $this->assertDatabaseHas('topics', ['name' => 'Política', 'slug' => 'politica']);
        $this->assertDatabaseHas('topics', ['name' => 'Tecnologia', 'slug' => 'tecnologia']);
        $this->assertDatabaseHas('topics', ['name' => 'Carros', 'slug' => 'carros']);
        $this->assertDatabaseHas('topic_aliases', [
            'name' => 'automotivo',
            'normalized_name' => 'automotivo',
        ]);
        $this->assertSame(count(config('topics.editorial_categories')), Topic::query()->count());
        $this->assertDatabaseHas('article_topic', [
            'article_id' => $article->id,
            'topic_id' => Topic::query()->where('slug', 'economia')->value('id'),
            'detected_by' => 'feed_category',
        ]);
    }

    public function test_feed_category_adds_a_broad_category_to_the_article_topics(): void
    {
        $article = Article::factory()->create();
        $article->feed->update(['category' => 'economy']);
        $fake = new FakeAiClient([
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => [['name' => 'Taxa Selic', 'type' => Topic::TYPE_TOPIC, 'confidence' => 0.9]],
                'importance' => 75,
                'is_news' => true,
                'language' => 'pt-BR',
            ],
        ]);
        $this->app->instance(AiClient::class, $fake);

        app(ArticleClassifier::class)->classifyAndPersist($article);

        $economia = Topic::query()->where('slug', 'economia')->firstOrFail();
        $this->assertDatabaseHas('article_topic', [
            'article_id' => $article->id,
            'topic_id' => $economia->id,
            'detected_by' => 'feed_category',
            'confidence' => 1,
        ]);
    }
}
