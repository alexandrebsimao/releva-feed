<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Services\News\HomepageNewsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(HomepageNewsService::class)->invalidate();
    }

    public function test_consolidated_news_page_is_public_and_lists_summary_topics_and_all_sources(): void
    {
        $cluster = NewsCluster::factory()->create([
            'slug' => 'toyota-suv-hibrido',
            'title' => 'Toyota confirma SUV híbrido',
            'summary' => 'A Toyota confirmou um novo SUV híbrido para o Brasil.',
            'why_it_matters' => 'O modelo amplia as opções eletrificadas no país.',
            'published_at' => now()->subHour(),
        ]);
        $topic = Topic::factory()->create(['name' => 'Toyota']);
        $first = Article::factory()->create(['original_title' => 'Toyota anuncia novo SUV híbrido']);
        $second = Article::factory()->create(['original_title' => 'Toyota confirma SUV para o Brasil']);
        $first->topics()->attach($topic, ['confidence' => 1, 'detected_by' => 'test']);
        $second->topics()->attach($topic, ['confidence' => 1, 'detected_by' => 'test']);
        $cluster->articles()->attach([$first->id, $second->id]);
        $first->feed->source->update(['name' => 'Motor1']);
        $second->feed->source->update(['name' => 'AutoPapo']);

        $this->get(route('news.show', $cluster))
            ->assertOk()
            ->assertSee('Resumo consolidado')
            ->assertSee('Toyota confirma SUV híbrido')
            ->assertSee('A Toyota confirmou um novo SUV híbrido para o Brasil.')
            ->assertSee('Por que importa:')
            ->assertSee('Toyota')
            ->assertSee('2 fontes')
            ->assertSee('Motor1')
            ->assertSee('AutoPapo')
            ->assertSee('Toyota anuncia novo SUV híbrido')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Ler fonte original');
    }

    public function test_hidden_cluster_is_not_public_but_can_be_opened_by_an_authenticated_user(): void
    {
        $cluster = NewsCluster::factory()->create(['public_status' => NewsCluster::STATUS_HIDDEN]);

        $this->get(route('news.show', $cluster))->assertNotFound();
        $this->actingAs(User::factory()->create())
            ->get(route('news.show', $cluster))
            ->assertOk();
    }

    public function test_homepage_sections_use_score_categories_and_chronological_now_feed(): void
    {
        config(['homepage.automatic_min_score' => 60]);
        $featured = $this->cluster('Destaque editorial', 10, NewsCluster::STATUS_FEATURED, 'Brasil');
        $highScore = $this->cluster('Tecnologia em alta', 90, NewsCluster::STATUS_AUTOMATIC, 'Tecnologia');
        $lowScore = $this->cluster('Automotivo pouco relevante', 20, NewsCluster::STATUS_AUTOMATIC, 'Automotivo');
        $hidden = $this->cluster('Nunca publicar', 100, NewsCluster::STATUS_HIDDEN, 'Brasil');
        $recent = $this->cluster('Acontecimento de agora', 80, NewsCluster::STATUS_AUTOMATIC, 'Brasil', now()->subMinutes(2));

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Destaques')
            ->assertSee('Brasil')
            ->assertSee('Tecnologia')
            ->assertSee('Automotivo')
            ->assertSee('Agora')
            ->assertSee($featured->title)
            ->assertSee($highScore->title)
            ->assertSee($recent->title)
            ->assertDontSee($lowScore->title)
            ->assertDontSee($hidden->title);
    }

    public function test_homepage_cache_is_invalidated_and_can_be_recalculated(): void
    {
        $service = app(HomepageNewsService::class);
        $this->get(route('home'))->assertOk();
        $cluster = $this->cluster('Notícia depois do cache', 90, NewsCluster::STATUS_AUTOMATIC, 'Tecnologia');

        $this->assertFalse($service->sections()['highlights']->contains('id', $cluster->id));
        $service->invalidate();
        $this->assertTrue($service->refresh()['highlights']->contains('id', $cluster->id));
        Cache::forget('homepage.news-clusters.v1');
    }

    private function cluster(
        string $title,
        int $score,
        string $status,
        string $category,
        ?Carbon $date = null,
    ): NewsCluster {
        $cluster = NewsCluster::factory()->create([
            'title' => $title,
            'general_score' => $score,
            'public_status' => $status,
            'category' => $category,
            'published_at' => $date ?? now()->subHour(),
            'last_article_at' => $date ?? now()->subHour(),
        ]);
        $cluster->articles()->attach(Article::factory()->create()->id);

        return $cluster;
    }
}
