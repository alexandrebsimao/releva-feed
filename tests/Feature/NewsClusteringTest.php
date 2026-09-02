<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Services\News\ClusterBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsClusteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_from_different_sources_about_the_same_event_share_one_cluster(): void
    {
        $toyota = Topic::factory()->create([
            'name' => 'Toyota',
            'type' => Topic::TYPE_COMPANY,
        ]);
        $hybridSuv = Topic::factory()->create([
            'name' => 'SUV híbrido',
            'type' => Topic::TYPE_PRODUCT,
        ]);
        $articles = [
            Article::factory()->create([
                'original_title' => 'Toyota anuncia novo SUV híbrido',
                'description' => 'A Toyota apresentou detalhes do utilitário híbrido que será vendido no Brasil.',
                'published_at' => now()->subHours(2),
            ]),
            Article::factory()->create([
                'original_title' => 'Toyota confirma SUV híbrido para o Brasil',
                'description' => 'A montadora confirmou a chegada do novo utilitário híbrido ao mercado brasileiro.',
                'published_at' => now()->subHour(),
            ]),
            Article::factory()->create([
                'original_title' => 'Novo utilitário híbrido da Toyota chega ao país',
                'description' => 'O SUV híbrido da Toyota será lançado no país e amplia a oferta da marca.',
                'published_at' => now(),
            ]),
        ];
        foreach ($articles as $article) {
            $this->attachTopics($article, [$toyota, $hybridSuv]);
        }

        $builder = app(ClusterBuilder::class);
        $builder->build($articles[0]);
        $builder->build($articles[1]);
        $builder->build($articles[2]);

        $this->assertDatabaseCount('news_clusters', 1);
        $this->assertDatabaseCount('cluster_article', 3);
        $cluster = NewsCluster::firstOrFail();
        $matchedArticle = $cluster->articles()->whereKey($articles[1]->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(0.65, (float) $matchedArticle->pivot->similarity_score);
        $this->assertSame(3, $cluster->articles()->count());
    }

    public function test_same_company_and_time_window_do_not_merge_unrelated_events(): void
    {
        $toyota = Topic::factory()->create([
            'name' => 'Toyota',
            'type' => Topic::TYPE_COMPANY,
        ]);
        $first = Article::factory()->create([
            'original_title' => 'Toyota apresenta caminhão elétrico para frotas',
            'description' => 'O novo caminhão elétrico foi desenvolvido para operações de transporte pesado.',
            'published_at' => now()->subHour(),
        ]);
        $second = Article::factory()->create([
            'original_title' => 'Toyota inaugura fábrica no interior de São Paulo',
            'description' => 'A nova unidade industrial começa a produzir componentes para o mercado nacional.',
            'published_at' => now(),
        ]);
        $this->attachTopics($first, [$toyota]);
        $this->attachTopics($second, [$toyota]);

        $builder = app(ClusterBuilder::class);
        $builder->build($first);
        $builder->build($second);

        $this->assertDatabaseCount('news_clusters', 2);
        $this->assertDatabaseCount('cluster_article', 2);
    }

    public function test_matching_content_outside_the_recent_window_creates_a_new_cluster(): void
    {
        $toyota = Topic::factory()->create([
            'name' => 'Toyota',
            'type' => Topic::TYPE_COMPANY,
        ]);
        $first = Article::factory()->create([
            'original_title' => 'Toyota confirma SUV híbrido para o Brasil',
            'description' => 'A montadora confirmou o lançamento do utilitário híbrido no mercado brasileiro.',
            'published_at' => now()->subDays(5),
        ]);
        $second = Article::factory()->create([
            'original_title' => 'Toyota confirma SUV híbrido para o Brasil',
            'description' => 'A empresa voltou a divulgar informações sobre o utilitário híbrido brasileiro.',
            'published_at' => now(),
        ]);
        $this->attachTopics($first, [$toyota]);
        $this->attachTopics($second, [$toyota]);

        $builder = app(ClusterBuilder::class);
        $builder->build($first);
        $builder->build($second);

        $this->assertDatabaseCount('news_clusters', 2);
        $this->assertDatabaseCount('cluster_article', 2);
    }

    public function test_similar_titles_without_shared_topics_are_not_merged(): void
    {
        $first = Article::factory()->create([
            'original_title' => 'Governo anuncia novo programa de energia solar',
            'description' => 'O programa federal prevê investimentos em energia renovável nos próximos anos.',
            'published_at' => now()->subMinutes(30),
        ]);
        $second = Article::factory()->create([
            'original_title' => 'Governo confirma novo programa de energia solar',
            'description' => 'A iniciativa estadual prevê incentivos para instalação de painéis em residências.',
            'published_at' => now(),
        ]);

        $builder = app(ClusterBuilder::class);
        $builder->build($first);
        $builder->build($second);

        $this->assertDatabaseCount('news_clusters', 2);
        $this->assertDatabaseCount('cluster_article', 2);
    }

    public function test_building_the_same_article_twice_is_idempotent(): void
    {
        $article = Article::factory()->create([
            'original_title' => 'Banco anuncia nova plataforma digital',
            'description' => 'A plataforma digital amplia os serviços oferecidos aos clientes do banco.',
            'published_at' => now()->subHour(),
        ]);
        $topic = Topic::factory()->create(['name' => 'Banco', 'type' => Topic::TYPE_COMPANY]);
        $this->attachTopics($article, [$topic]);
        $builder = app(ClusterBuilder::class);

        $first = $builder->build($article);
        $second = $builder->build($article->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('news_clusters', 1);
        $this->assertDatabaseCount('cluster_article', 1);
        $this->assertNotNull($second->published_at);
        $this->assertNotNull($second->last_article_at);
    }

    /** @param list<Topic> $topics */
    private function attachTopics(Article $article, array $topics): void
    {
        $article->topics()->attach(collect($topics)->mapWithKeys(fn (Topic $topic): array => [
            $topic->id => ['confidence' => 0.95, 'detected_by' => 'test'],
        ])->all());
    }
}
