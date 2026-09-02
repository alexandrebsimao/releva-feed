<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use App\Services\Topic\UserTopicPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTopicPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_topics_requires_authentication(): void
    {
        $this->get('/my-topics')->assertRedirect('/login');
    }

    public function test_user_can_follow_exclude_and_remove_a_topic_preference(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['name' => 'Inteligência Artificial']);

        $this->actingAs($user)
            ->post(route('my-topics.follow', $topic))
            ->assertRedirect(route('my-topics.index'));

        $this->assertDatabaseHas('user_topic_preferences', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => UserTopicPreference::PRIORITY_NORMAL,
        ]);

        // Switching the choice updates the user's existing preference instead of creating a duplicate.
        $this->actingAs($user)->post(route('my-topics.exclude', $topic));

        $this->assertDatabaseHas('user_topic_preferences', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_EXCLUDE,
        ]);
        $this->assertDatabaseCount('user_topic_preferences', 1);

        $this->actingAs($user)
            ->delete(route('my-topics.destroy', $topic))
            ->assertRedirect(route('my-topics.index'));

        $this->assertDatabaseMissing('user_topic_preferences', ['user_id' => $user->id, 'topic_id' => $topic->id]);
    }

    public function test_user_can_change_preference_priority(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'priority' => UserTopicPreference::PRIORITY_NORMAL,
        ]);

        $this->actingAs($user)
            ->patch(route('my-topics.update', $topic), ['priority' => UserTopicPreference::PRIORITY_HIGH])
            ->assertRedirect(route('my-topics.index'));

        $this->assertDatabaseHas('user_topic_preferences', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'priority' => UserTopicPreference::PRIORITY_HIGH,
        ]);
    }

    public function test_search_only_returns_active_unselected_topics(): void
    {
        $user = User::factory()->create();
        $selected = Topic::factory()->create(['name' => 'Tecnologia espacial']);
        Topic::factory()->create(['name' => 'Tecnologia médica']);
        Topic::factory()->create(['name' => 'Tecnologia inativa', 'active' => false]);
        UserTopicPreference::factory()->create(['user_id' => $user->id, 'topic_id' => $selected->id]);

        $this->actingAs($user)
            ->get(route('my-topics.index', ['q' => 'tecnologia']))
            ->assertOk()
            ->assertSee('Tecnologia médica')
            ->assertDontSee('Tecnologia inativa');

    }

    public function test_excluded_topic_has_precedence_over_followed_topic_in_the_radar(): void
    {
        $user = User::factory()->create();
        $followed = Topic::factory()->create(['name' => 'Tecnologia']);
        $excluded = Topic::factory()->create(['name' => 'Inteligência Artificial']);
        $allowedArticle = Article::factory()->create(['original_title' => 'Somente tecnologia']);
        $blockedArticle = Article::factory()->create(['original_title' => 'Tecnologia e IA']);
        $allowedArticle->topics()->attach($followed, ['confidence' => 1, 'detected_by' => 'manual']);
        $blockedArticle->topics()->attach([$followed->id, $excluded->id], ['confidence' => 1, 'detected_by' => 'manual']);
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $followed->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
        ]);
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $excluded->id,
            'preference' => UserTopicPreference::PREFERENCE_EXCLUDE,
        ]);

        $service = app(UserTopicPreferenceService::class);

        $this->assertTrue($service->shouldIncludeArticle($user, $allowedArticle));
        $this->assertFalse($service->shouldIncludeArticle($user, $blockedArticle));
        $this->assertSame([$allowedArticle->id], $service->radarQuery($user)->pluck('id')->all());
        $this->actingAs($user)->get('/dashboard')->assertSee('Somente tecnologia')->assertDontSee('Tecnologia e IA');
    }

    public function test_preferences_are_isolated_between_users(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $topic = Topic::factory()->create(['name' => 'Economia']);

        $this->actingAs($firstUser)->post(route('my-topics.follow', $topic));

        $this->actingAs($secondUser)->get('/my-topics')->assertSee('Economia');
        $this->assertDatabaseHas('user_topic_preferences', ['user_id' => $firstUser->id, 'topic_id' => $topic->id]);
        $this->assertDatabaseMissing('user_topic_preferences', ['user_id' => $secondUser->id, 'topic_id' => $topic->id]);
    }
}
