<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use App\Services\News\GeneralScoreCalculator;
use App\Services\News\UserRelevanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_score_is_higher_for_an_important_recent_cluster_with_strong_sources(): void
    {
        $important = NewsCluster::factory()->create([
            'importance_score' => 90,
            'published_at' => now()->subHour(),
            'last_article_at' => now()->subHour(),
        ]);
        $important->articles()->attach([
            $this->articleWithSourcePriority(100)->id,
            $this->articleWithSourcePriority(90)->id,
            $this->articleWithSourcePriority(80)->id,
        ]);
        $old = NewsCluster::factory()->create([
            'importance_score' => 10,
            'published_at' => now()->subDays(5),
            'last_article_at' => now()->subDays(5),
        ]);
        $old->articles()->attach($this->articleWithSourcePriority(0)->id);

        $calculator = app(GeneralScoreCalculator::class);
        $importantScore = $calculator->calculateAndPersist($important->fresh());
        $oldScore = $calculator->calculateAndPersist($old->fresh());

        $this->assertGreaterThan($oldScore, $importantScore);
        $this->assertSame($importantScore, $important->fresh()->general_score);
        $this->assertGreaterThanOrEqual(0, $importantScore);
        $this->assertLessThanOrEqual(100, $importantScore);
    }

    public function test_general_score_uses_configured_weights(): void
    {
        config(['scoring.general.weights' => [
            'importance' => 1.0,
            'recency' => 0.0,
            'source_count' => 0.0,
            'source_priority' => 0.0,
            'novelty' => 0.0,
        ]]);
        $cluster = NewsCluster::factory()->create(['importance_score' => 73]);
        $cluster->articles()->attach(Article::factory()->create()->id);

        $this->assertSame(73, app(GeneralScoreCalculator::class)->calculate($cluster));
    }

    public function test_user_score_rewards_followed_topic_priority(): void
    {
        $topic = Topic::factory()->create();
        $cluster = NewsCluster::factory()->create([
            'importance_score' => 80,
            'last_article_at' => now()->subHour(),
        ]);
        $article = Article::factory()->create();
        $article->topics()->attach($topic, ['confidence' => 1, 'detected_by' => 'test']);
        $cluster->articles()->attach($article);
        $lowPriorityUser = User::factory()->create();
        $highPriorityUser = User::factory()->create();
        UserTopicPreference::factory()->create([
            'user_id' => $lowPriorityUser->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => UserTopicPreference::PRIORITY_LOW,
        ]);
        UserTopicPreference::factory()->create([
            'user_id' => $highPriorityUser->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => UserTopicPreference::PRIORITY_HIGH,
        ]);

        $calculator = app(UserRelevanceCalculator::class);
        $lowScore = $calculator->calculate($lowPriorityUser, $cluster->fresh());
        $highScore = $calculator->calculate($highPriorityUser, $cluster->fresh());

        $this->assertGreaterThan($lowScore, $highScore);
        $this->assertNull($cluster->fresh()->getAttribute('user_relevance_score'));
    }

    public function test_user_exclusion_always_returns_zero_even_when_topic_is_followed(): void
    {
        $followed = Topic::factory()->create(['name' => 'Tecnologia']);
        $excluded = Topic::factory()->create(['name' => 'Inteligência Artificial']);
        $cluster = NewsCluster::factory()->create(['importance_score' => 100]);
        $article = Article::factory()->create();
        $article->topics()->attach([
            $followed->id => ['confidence' => 1, 'detected_by' => 'test'],
            $excluded->id => ['confidence' => 1, 'detected_by' => 'test'],
        ]);
        $cluster->articles()->attach($article);
        $user = User::factory()->create();
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $followed->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => UserTopicPreference::PRIORITY_HIGH,
        ]);
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $excluded->id,
            'preference' => UserTopicPreference::PREFERENCE_EXCLUDE,
            'priority' => UserTopicPreference::PRIORITY_HIGH,
        ]);

        $this->assertSame(0, app(UserRelevanceCalculator::class)->calculate($user, $cluster));
    }

    public function test_user_without_a_followed_topic_gets_zero_relevance(): void
    {
        $topic = Topic::factory()->create();
        $cluster = NewsCluster::factory()->create();
        $article = Article::factory()->create();
        $article->topics()->attach($topic, ['confidence' => 1, 'detected_by' => 'test']);
        $cluster->articles()->attach($article);

        $this->assertSame(0, app(UserRelevanceCalculator::class)->calculate(User::factory()->create(), $cluster));
    }

    private function articleWithSourcePriority(int $priority): Article
    {
        $article = Article::factory()->create();
        $article->load('feed.source');
        $article->feed->source->update(['priority' => $priority]);

        return $article;
    }
}
