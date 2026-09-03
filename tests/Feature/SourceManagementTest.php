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
        $user = User::factory()->create(['is_admin' => true]);
        $source = Source::factory()->create(['name' => 'Observatório', 'domain' => 'observatorio.test']);
        $user->sources()->attach($source);
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
            ->assertSee('Principal')
            ->assertSee('https://observatorio.test/feed.xml')
            ->assertSee(now()->format('d/m/Y'));
    }

    public function test_authenticated_user_can_create_and_update_a_source(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

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

    public function test_regular_user_cannot_change_source_active_status(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['active' => true]);

        $this->actingAs($user)->put(route('sources.update', $source), [
            'name' => $source->name,
            'slug' => $source->slug,
            'domain' => $source->domain,
            'priority' => $source->priority,
            'active' => '0',
        ])->assertRedirect(route('sources.edit', $source));

        $this->assertDatabaseHas('sources', ['id' => $source->id, 'active' => true]);
    }

    public function test_regular_user_cannot_delete_a_global_source(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();
        $user->sources()->attach($source);

        $this->actingAs($user)
            ->delete(route('sources.destroy', $source))
            ->assertForbidden();

        $this->assertDatabaseHas('sources', ['id' => $source->id]);
    }

    public function test_user_sees_only_selected_sources_and_can_add_or_remove_catalog_sources(): void
    {
        $user = User::factory()->create();
        $selected = Source::factory()->create(['name' => 'Fonte escolhida']);
        $available = Source::factory()->create(['name' => 'Fonte disponível']);
        $user->sources()->attach($selected);
        $available->feeds()->create([
            'name' => 'RSS disponível',
            'url' => 'https://disponivel.example.com/feed.xml',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('sources.index'))
            ->assertOk()
            ->assertSee('Fonte escolhida')
            ->assertSee('Fonte disponível')
            ->assertSee('RSS disponível')
            ->assertSee('https://disponivel.example.com/feed.xml')
            ->assertSee('Remover da minha lista')
            ->assertSee('Adicionar à minha lista');

        $this->actingAs($user)
            ->post(route('sources.select', $available))
            ->assertRedirect(route('sources.index'));

        $this->assertDatabaseHas('user_source_preferences', ['user_id' => $user->id, 'source_id' => $available->id]);

        $this->actingAs($user)
            ->delete(route('sources.deselect', $selected))
            ->assertRedirect(route('sources.index'));

        $this->assertDatabaseMissing('user_source_preferences', ['user_id' => $user->id, 'source_id' => $selected->id]);
    }

    public function test_user_created_source_is_selected_automatically(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sources.store'), [
                'name' => 'Minha fonte nova',
                'slug' => 'minha-fonte-nova',
                'domain' => 'minhafonte.example.com',
                'priority' => 10,
                'active' => '1',
            ])
            ->assertRedirect();

        $source = Source::query()->where('slug', 'minha-fonte-nova')->firstOrFail();

        $this->assertDatabaseHas('user_source_preferences', ['user_id' => $user->id, 'source_id' => $source->id]);
    }
}
