<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_page_explains_and_links_to_both_personal_settings(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $user->topicPreferences()->create([
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
        ]);
        $user->sources()->attach(Source::factory()->create());

        $this->actingAs($user)
            ->get(route('preferences.index'))
            ->assertOk()
            ->assertSee('Preferências')
            ->assertSee('1 assunto')
            ->assertSee('1 fonte')
            ->assertSee(route('my-topics.index'))
            ->assertSee(route('sources.index'));
    }
}
