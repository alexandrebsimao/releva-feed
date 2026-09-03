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
        $this->assertDatabaseHas('user_source_preferences', [
            'user_id' => $user->id,
            'source_id' => $newSource->id,
        ]);
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

    public function test_user_can_view_a_feed_with_articles_from_selected_sources_only(): void
    {
        $user = User::factory()->create();
        $selected = Source::factory()->create(['name' => 'Fonte acompanhada']);
        $unselected = Source::factory()->create(['name' => 'Fonte não acompanhada']);
        $user->sources()->attach($selected);

        $selectedArticle = $this->articleFrom($selected, 'Artigo da fonte acompanhada');
        $this->articleFrom($unselected, 'Artigo da fonte não acompanhada');

        $this->actingAs($user)
            ->get(route('my-sources.index'))
            ->assertOk()
            ->assertSee($selectedArticle->original_title)
            ->assertDontSee('Artigo da fonte não acompanhada')
            ->assertSee('Artigos das minhas fontes')
            ->assertSee('Minhas fontes');
    }

    public function test_my_sources_feed_cannot_be_expanded_with_another_source_filter(): void
    {
        $user = User::factory()->create();
        $selected = Source::factory()->create(['name' => 'Fonte do usuário']);
        $other = Source::factory()->create(['name' => 'Fonte externa']);
        $user->sources()->attach($selected);
        $this->articleFrom($selected, 'Artigo da fonte do usuário');
        $this->articleFrom($other, 'Artigo da fonte externa');

        $this->actingAs($user)
            ->get(route('my-sources.index', ['sources' => [$other->id]]))
            ->assertOk()
            ->assertSee('Artigo da fonte do usuário')
            ->assertDontSee('Artigo da fonte externa')
            ->assertSee('Fonte do usuário')
            ->assertDontSee('Fonte externa');
    }

    public function test_my_feed_shows_the_publication_date_for_an_unclustered_article(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();
        $article = $this->articleFrom($source, 'Artigo sem cluster');
        $article->update(['published_at' => now()->subHours(3)]);
        $article->refresh();

        $this->actingAs($user)
            ->get(route('dashboard', ['view' => 'all']))
            ->assertOk()
            ->assertSee('Publicado '.$article->published_at->format('d/m/Y H:i'));
    }

    public function test_my_feed_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();
        $included = $this->articleFrom($source, 'Artigo de tecnologia', 'technology');
        $this->articleFrom($source, 'Artigo de economia', 'economy');

        $this->actingAs($user)
            ->get(route('dashboard', ['view' => 'all', 'category' => 'tecnologia']))
            ->assertOk()
            ->assertSee($included->original_title)
            ->assertDontSee('Artigo de economia')
            ->assertSee('Filtrar por categoria')
            ->assertSee('Tecnologia');
    }

    public function test_unified_feed_can_switch_between_all_articles_and_selected_sources(): void
    {
        $user = User::factory()->create();
        $selected = Source::factory()->create();
        $unselected = Source::factory()->create();
        $user->sources()->attach($selected);

        $this->articleFrom($selected, 'Artigo da fonte selecionada');
        $this->articleFrom($unselected, 'Artigo da fonte não selecionada');

        $this->actingAs($user)
            ->get(route('dashboard', ['view' => 'all']))
            ->assertOk()
            ->assertSee('Artigo da fonte selecionada')
            ->assertSee('Artigo da fonte não selecionada')
            ->assertSee('Todos os artigos');

        $this->actingAs($user)
            ->get(route('dashboard', ['view' => 'sources']))
            ->assertOk()
            ->assertSee('Artigo da fonte selecionada')
            ->assertDontSee('Artigo da fonte não selecionada')
            ->assertSee('Artigos das minhas fontes');
    }

    public function test_my_feed_can_filter_articles_by_source_without_changing_the_feed_mode(): void
    {
        $user = User::factory()->create();
        $firstSource = Source::factory()->create(['name' => 'Fonte filtrada']);
        $secondSource = Source::factory()->create(['name' => 'Fonte ignorada']);
        $this->articleFrom($firstSource, 'Artigo filtrado');
        $this->articleFrom($secondSource, 'Artigo fora do filtro');

        $this->actingAs($user)
            ->get(route('dashboard', ['view' => 'all', 'sources' => [$firstSource->id]]))
            ->assertOk()
            ->assertSee('Artigo filtrado')
            ->assertDontSee('Artigo fora do filtro')
            ->assertSee('Todos os artigos')
            ->assertSee('Filtrar por fonte')
            ->assertSee('1 fonte selecionada');
    }

    private function articleFrom(Source $source, string $title, ?string $category = null): Article
    {
        $feed = Feed::factory()->create(['source_id' => $source->id, 'category' => $category]);

        return Article::factory()->create([
            'feed_id' => $feed->id,
            'original_title' => $title,
        ]);
    }
}
