<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_a_feed_to_a_source(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();

        $this->actingAs($user)
            ->post(route('sources.feeds.store', $source), [
                'name' => 'Feed principal',
                'url' => 'https://example.com/rss.xml',
                'category' => 'Geral',
                'active' => '1',
            ])
            ->assertRedirect(route('sources.edit', $source));

        $this->assertDatabaseHas('feeds', [
            'source_id' => $source->id,
            'name' => 'Feed principal',
            'url' => 'https://example.com/rss.xml',
        ]);
    }

    public function test_feed_url_must_be_http_or_https(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();

        $this->actingAs($user)
            ->from(route('sources.feeds.create', $source))
            ->post(route('sources.feeds.store', $source), [
                'name' => 'Feed inválido',
                'url' => 'ftp://example.com/feed.xml',
                'active' => '1',
            ])
            ->assertRedirect(route('sources.feeds.create', $source))
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('feeds', 0);
    }

    public function test_new_feed_form_lists_pre_registered_rss_options(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['name' => 'Fonte cadastrada']);
        $source->feeds()->create([
            'name' => 'RSS principal',
            'url' => 'https://catalogo.example.com/rss.xml',
            'category' => 'general',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('sources.feeds.create', $source))
            ->assertOk()
            ->assertSee('RSS pré-cadastrado')
            ->assertSee('RSS principal')
            ->assertSee('Fonte cadastrada');
    }

    public function test_pre_registered_rss_cannot_be_added_twice(): void
    {
        $user = User::factory()->create();
        $contextSource = Source::factory()->create();
        $source = Source::factory()->create(['name' => 'Fonte do catálogo']);
        $source->feeds()->create([
            'name' => 'RSS principal',
            'url' => 'https://catalogo.example.com/rss.xml',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('sources.feeds.store', $contextSource), [
                'name' => 'RSS principal',
                'url' => 'https://catalogo.example.com/rss.xml',
                'active' => '1',
            ])
            ->assertRedirect(route('sources.edit', $source))
            ->assertSessionHas('status', 'Esse RSS já está cadastrado na fonte Fonte do catálogo.');

        $this->assertDatabaseCount('feeds', 1);
    }
}
