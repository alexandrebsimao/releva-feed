<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Topic;
use App\Models\TopicAlias;
use App\Models\User;
use App\Models\UserTopicPreference;
use App\Models\UserTopicSuggestion;
use App\Services\Topic\UserTopicDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_suggests_frequent_topics_from_sources_related_to_followed_topics(): void
    {
        $user = User::factory()->create();
        $followed = Topic::factory()->create(['name' => 'Tecnologia']);
        $candidate = Topic::factory()->create(['name' => 'Cibersegurança']);
        $generic = Topic::factory()->create(['name' => 'Notícia']);
        $excluded = Topic::factory()->create(['name' => 'Publicidade']);
        UserTopicPreference::factory()->create(['user_id' => $user->id, 'topic_id' => $followed->id]);
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $excluded->id,
            'preference' => UserTopicPreference::PREFERENCE_EXCLUDE,
        ]);

        foreach (range(1, 4) as $index) {
            $article = Article::factory()->create(['published_at' => now()->subDays(2)]);
            $article->topics()->attach([
                $followed->id => ['confidence' => 1, 'detected_by' => 'test'],
                $candidate->id => ['confidence' => 1, 'detected_by' => 'test'],
                $generic->id => ['confidence' => 1, 'detected_by' => 'test'],
                $excluded->id => ['confidence' => 1, 'detected_by' => 'test'],
            ]);
        }

        $suggestions = app(UserTopicDiscoveryService::class)->discover($user);

        $this->assertCount(1, $suggestions);
        $this->assertSame($candidate->id, $suggestions->first()->topic_id);
        $this->assertSame(4, $suggestions->first()->article_count);
        $this->assertDatabaseMissing('user_topic_preferences', ['user_id' => $user->id, 'topic_id' => $candidate->id]);
    }

    public function test_discovery_ignores_aliases_existing_topics_and_limits_suggestions(): void
    {
        config(['topic_discovery.max_suggestions' => 2]);
        $user = User::factory()->create();
        $followed = Topic::factory()->create(['name' => 'Automotivo']);
        UserTopicPreference::factory()->create(['user_id' => $user->id, 'topic_id' => $followed->id]);
        $canonical = Topic::factory()->create(['name' => 'Apple']);
        $aliasTopic = Topic::factory()->create(['name' => 'Maçã']);
        TopicAlias::factory()->create([
            'topic_id' => $canonical->id,
            'name' => 'Maçã',
            'normalized_name' => 'maca',
        ]);
        $candidates = collect(range(1, 3))->map(fn (int $index): Topic => Topic::factory()->create(['name' => 'Assunto '.$index]));

        foreach (range(1, 3) as $index) {
            $article = Article::factory()->create(['published_at' => now()->subDay()]);
            $topicIds = [$followed->id, $canonical->id, $aliasTopic->id, ...$candidates->pluck('id')->all()];
            $article->topics()->attach(collect($topicIds)->mapWithKeys(fn (int $topicId): array => [
                $topicId => ['confidence' => 1, 'detected_by' => 'test'],
            ])->all());
        }

        $suggestions = app(UserTopicDiscoveryService::class)->discover($user);

        $this->assertCount(2, $suggestions);
        $this->assertNotContains($aliasTopic->id, $suggestions->pluck('topic_id')->all());
        $this->assertContains($canonical->id, $suggestions->pluck('topic_id')->all());
    }

    public function test_ignored_suggestion_disappears_and_is_scoped_to_its_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $suggestion = UserTopicSuggestion::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('my-topics.suggestions.ignore', $suggestion))
            ->assertRedirect(route('my-topics.index'));

        $this->assertNotNull($suggestion->fresh()->ignored_at);
        $this->actingAs($other)
            ->post(route('my-topics.suggestions.ignore', $suggestion))
            ->assertNotFound();
    }

    public function test_my_topics_displays_discovery_suggestions_without_following_them(): void
    {
        $user = User::factory()->create();
        $suggestion = UserTopicSuggestion::factory()->create(['user_id' => $user->id, 'article_count' => 42]);

        $this->actingAs($user)
            ->get(route('my-topics.index'))
            ->assertOk()
            ->assertSee('Assuntos encontrados nas suas fontes')
            ->assertSee($suggestion->topic->name)
            ->assertSee('42 notícias')
            ->assertSee('Acompanhar')
            ->assertSee('Ignorar');
    }
}
