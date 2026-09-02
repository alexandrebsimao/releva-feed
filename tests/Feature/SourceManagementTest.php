<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sources_area_requires_authentication(): void
    {
        $this->get('/sources')->assertRedirect('/login');
        $this->get('/sources/create')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_see_sources_with_feed_count_and_last_success(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['name' => 'Observatório', 'domain' => 'observatorio.test']);
        $source->feeds()->createMany([
            ['name' => 'Principal', 'url' => 'https://observatorio.test/feed.xml', 'last_success_at' => now()->subHour()],
            ['name' => 'Tecnologia', 'url' => 'https://observatorio.test/tech.xml', 'last_success_at' => now()],
        ]);

        $this->actingAs($user)
            ->get('/sources')
            ->assertOk()
            ->assertSee('Observatório')
            ->assertSee('observatorio.test')
            ->assertSee('2')
            ->assertSee(now()->format('d/m/Y'));
    }

    public function test_authenticated_user_can_create_and_update_a_source(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/sources', [
            'name' => 'The News',
            'slug' => 'the-news',
            'domain' => 'thenews.com',
            'priority' => 50,
            'active' => '1',
        ]);

        $source = Source::query()->firstOrFail();
        $response->assertRedirect(route('sources.edit', $source));
        $this->assertDatabaseHas('sources', ['slug' => 'the-news', 'active' => true]);

        $this->actingAs($user)->put(route('sources.update', $source), [
            'name' => 'The News Atualizado',
            'slug' => 'the-news',
            'domain' => 'thenews.com',
            'priority' => 80,
            'active' => '0',
        ])->assertRedirect(route('sources.edit', $source));

        $this->assertDatabaseHas('sources', ['id' => $source->id, 'name' => 'The News Atualizado', 'priority' => 80, 'active' => false]);
    }
}
