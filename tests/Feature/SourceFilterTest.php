<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Feed;
use App\Models\NewsCluster;
use App\Models\Source;
use App\Models\User;
use App\Services\News\HomepageNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(HomepageNewsService::class)->invalidate();
    }

    public function test_articles_can_be_filtered_by_multiple_sources(): void
    {
        $user = User::factory()->create();
        $first = Source::factory()->create(['name' => 'Fonte A']);
        $second = Source::factory()->create(['name' => 'Fonte B']);
        $third = Source::factory()->create(['name' => 'Fonte C']);

        $this->articleFrom($first, 'Artigo da Fonte A');
        $this->articleFrom($second, 'Artigo da Fonte B');
        $this->articleFrom($third, 'Artigo da Fonte C');

        $this->actingAs($user)
            ->get(route('articles.index', ['sources' => [$first->id, $second->id]]))
            ->assertOk()
            ->assertSee('Artigo da Fonte A')
            ->assertSee('Artigo da Fonte B')
            ->assertDontSee('Artigo da Fonte C')
            ->assertSee('Filtrar por fonte');
    }

    public function test_homepage_feed_can_be_filtered_by_source(): void
    {
        $first = Source::factory()->create(['name' => 'Fonte escolhida']);
        $second = Source::factory()->create(['name' => 'Fonte fora do filtro']);

        $visible = NewsCluster::factory()->create([
            'title' => 'Acontecimento da fonte escolhida',
            'public_status' => NewsCluster::STATUS_FEATURED,
            'general_score' => 90,
        ]);
        $hidden = NewsCluster::factory()->create([
            'title' => 'Acontecimento de outra fonte',
            'public_status' => NewsCluster::STATUS_FEATURED,
            'general_score' => 90,
        ]);
        $visible->articles()->attach($this->articleFrom($first, 'Cobertura escolhida'));
        $hidden->articles()->attach($this->articleFrom($second, 'Cobertura fora do filtro'));

        $this->get(route('home', ['sources' => [$first->id]]))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee($hidden->title)
            ->assertSee('Filtrar por fonte');
    }

    public function test_feed_can_use_an_existing_source_or_create_one_in_the_main_catalog(): void
    {
        $user = User::factory()->create();
        $contextSource = Source::factory()->create();
        $existingSource = Source::factory()->create(['name' => 'Catálogo existente']);

        $this->actingAs($user)
            ->post(route('sources.feeds.store', $contextSource), [
                'source_id' => $existingSource->id,
                'name' => 'Feed existente',
                'url' => 'https://existing.example.com/feed.xml',
                'active' => '1',
            ])
            ->assertRedirect(route('sources.edit', $existingSource));

        $this->actingAs($user)
            ->post(route('sources.feeds.store', $contextSource), [
                'new_source_name' => 'Nova fonte inline',
                'new_source_domain' => 'nova-fonte.example.com',
                'name' => 'Feed novo',
                'url' => 'https://nova-fonte.example.com/feed.xml',
                'active' => '1',
            ])
            ->assertRedirect();

        $newSource = Source::query()->where('domain', 'nova-fonte.example.com')->firstOrFail();
        $this->assertDatabaseHas('feeds', [
            'source_id' => $newSource->id,
            'name' => 'Feed novo',
        ]);
        $this->assertDatabaseHas('feeds', [
            'source_id' => $existingSource->id,
            'name' => 'Feed existente',
        ]);
    }

    public function test_source_search_returns_matching_pre_registered_sources(): void
    {
        Source::factory()->create(['name' => 'Jornal da Manhã', 'domain' => 'manha.example.com']);
        Source::factory()->create(['name' => 'Outro Jornal', 'domain' => 'outro.example.com']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('sources.search', ['q' => 'manha']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Jornal da Manhã', 'domain' => 'manha.example.com'])
            ->assertJsonMissing(['name' => 'Outro Jornal']);
    }

    private function articleFrom(Source $source, string $title): Article
    {
        $feed = Feed::factory()->create(['source_id' => $source->id]);

        return Article::factory()->create([
            'feed_id' => $feed->id,
            'original_title' => $title,
        ]);
    }
}
