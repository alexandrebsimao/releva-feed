<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Topic;
use App\Models\TopicAlias;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_topics_area_requires_authentication(): void
    {
        $this->get('/topics')->assertRedirect('/login');
        $this->get('/topics/create')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_a_topic_and_normalized_aliases(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/topics', [
            'name' => 'Inteligência Artificial',
            'slug' => 'inteligencia-artificial',
            'type' => Topic::TYPE_TECHNOLOGY,
            'status' => Topic::STATUS_APPROVED,
            'active' => '1',
            'aliases' => "IA\n ia \nArtificial Intelligence",
        ]);

        $topic = Topic::query()->firstOrFail();
        $response->assertRedirect(route('topics.edit', $topic));
        $this->assertDatabaseHas('topics', ['name' => 'Inteligência Artificial', 'status' => Topic::STATUS_APPROVED]);
        $this->assertDatabaseHas('topic_aliases', ['topic_id' => $topic->id, 'name' => 'IA', 'normalized_name' => 'ia']);
        $this->assertSame(2, TopicAlias::query()->where('topic_id', $topic->id)->count());
    }

    public function test_topics_page_groups_statuses_and_shows_article_count(): void
    {
        $user = User::factory()->create();
        $approved = Topic::factory()->create(['name' => 'Aprovado', 'status' => Topic::STATUS_APPROVED]);
        $suggested = Topic::factory()->create(['name' => 'Sugerido', 'status' => Topic::STATUS_SUGGESTED]);
        Topic::factory()->create(['name' => 'Ignorado', 'status' => Topic::STATUS_IGNORED, 'active' => false]);
        Article::factory()->create()->topics()->attach($approved, ['confidence' => 1, 'detected_by' => 'manual']);

        $this->actingAs($user)->get('/topics')->assertOk()->assertSee('Aprovado')->assertSee('Sugerido')->assertSee('Ignorado')->assertSee('artigo(s) associado(s)');
    }

    public function test_user_can_edit_a_topic_and_sync_its_aliases(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['name' => 'IA', 'slug' => 'ia']);
        $topic->aliases()->create(['name' => 'IA', 'normalized_name' => 'ia']);

        $this->actingAs($user)->put(route('topics.update', $topic), [
            'name' => 'Inteligência Artificial',
            'slug' => 'inteligencia-artificial',
            'type' => Topic::TYPE_TECHNOLOGY,
            'status' => Topic::STATUS_APPROVED,
            'active' => '1',
            'aliases' => "AI\nArtificial Intelligence",
        ])->assertRedirect(route('topics.edit', $topic));

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'name' => 'Inteligência Artificial',
            'slug' => 'inteligencia-artificial',
        ]);
        $this->assertDatabaseMissing('topic_aliases', ['topic_id' => $topic->id, 'normalized_name' => 'ia']);
        $this->assertDatabaseHas('topic_aliases', ['topic_id' => $topic->id, 'normalized_name' => 'ai']);
        $this->assertDatabaseHas('topic_aliases', ['topic_id' => $topic->id, 'normalized_name' => 'artificial intelligence']);
    }

    public function test_user_can_approve_and_ignore_topics(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['status' => Topic::STATUS_SUGGESTED, 'active' => true]);

        $this->actingAs($user)->patch(route('topics.approve', $topic))->assertRedirect(route('topics.index'));
        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'status' => Topic::STATUS_APPROVED, 'active' => true]);

        $this->actingAs($user)->patch(route('topics.ignore', $topic))->assertRedirect(route('topics.index'));
        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'status' => Topic::STATUS_IGNORED, 'active' => false]);
    }
}
