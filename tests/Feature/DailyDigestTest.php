<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigestJob;
use App\Mail\DailyDigestMail;
use App\Models\Article;
use App\Models\DailyDigest;
use App\Models\DigestItem;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use App\Services\News\DigestBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_selects_relevant_unexcluded_unsent_clusters_in_score_order(): void
    {
        $user = User::factory()->create([
            'digest_min_score' => 1,
            'digest_max_items' => 10,
        ]);
        $followed = Topic::factory()->create(['name' => 'Toyota']);
        $excluded = Topic::factory()->create(['name' => 'Recall']);
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
        ]);

        $selected = $this->cluster('Toyota lança modelo', now()->subHour(), [$followed]);
        $blocked = $this->cluster('Toyota faz recall', now()->subMinutes(30), [$followed, $excluded]);
        $unrelated = $this->cluster('Economia sem interesse', now()->subMinutes(20), [Topic::factory()->create()]);
        $sent = $this->cluster('Toyota já enviada', now()->subMinutes(10), [$followed]);
        $sentDigest = DailyDigest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay(),
            'status' => DailyDigest::STATUS_SENT,
            'sent_at' => now()->subHours(2),
        ]);
        $sentDigest->items()->create(['news_cluster_id' => $sent->id, 'score' => 90, 'position' => 1]);

        $digest = app(DigestBuilder::class)->build($user, now());

        $this->assertSame(DailyDigest::STATUS_READY, $digest->status);
        $this->assertSame(3, $digest->analyzed_count);
        $this->assertSame(1, $digest->related_count);
        $this->assertSame([$selected->id], $digest->items->pluck('news_cluster_id')->all());
        $this->assertSame(1, $digest->items->first()->position);
        $this->assertSame($selected->id, $digest->items->first()->news_cluster_id);
        $this->assertNotContains($blocked->id, $digest->items->pluck('news_cluster_id')->all());
        $this->assertNotContains($sent->id, $digest->items->pluck('news_cluster_id')->all());
        $this->assertNotContains($unrelated->id, $digest->items->pluck('news_cluster_id')->all());
    }

    public function test_builder_does_not_fill_digest_with_low_relevance_news(): void
    {
        $user = User::factory()->create(['digest_min_score' => 100]);
        $topic = Topic::factory()->create();
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
        ]);
        $this->cluster('Notícia abaixo do mínimo', now()->subHour(), [$topic]);

        $digest = app(DigestBuilder::class)->build($user, now());

        $this->assertSame(DailyDigest::STATUS_EMPTY, $digest->status);
        $this->assertCount(0, $digest->items);
    }

    public function test_builder_respects_previous_digest_period_and_item_limit(): void
    {
        $user = User::factory()->create(['digest_min_score' => 1, 'digest_max_items' => 2]);
        $topic = Topic::factory()->create();
        UserTopicPreference::factory()->create(['user_id' => $user->id, 'topic_id' => $topic->id]);
        $sentAt = now()->subHours(12);
        DailyDigest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay(),
            'status' => DailyDigest::STATUS_SENT,
            'sent_at' => $sentAt,
        ]);
        $beforePeriod = $this->cluster('Antes do período', $sentAt->copy()->subMinute(), [$topic]);
        $first = $this->cluster('Primeira do período', $sentAt->copy()->addHour(), [$topic]);
        $second = $this->cluster('Segunda do período', $sentAt->copy()->addHours(2), [$topic]);
        $third = $this->cluster('Terceira do período', $sentAt->copy()->addHours(3), [$topic]);

        $digest = app(DigestBuilder::class)->build($user, now());

        $this->assertSame([$third->id, $second->id], $digest->items->pluck('news_cluster_id')->all());
        $this->assertNotContains($beforePeriod->id, $digest->items->pluck('news_cluster_id')->all());
        $this->assertSame(3, $digest->analyzed_count);
        $this->assertSame(2, $digest->items->count());
        $this->assertSame(2, $digest->items->last()->position);
        $this->assertNotSame($first->id, $digest->items->last()->news_cluster_id);
    }

    public function test_send_job_sends_ready_digest_and_marks_it_sent(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['name' => 'Tecnologia']);
        UserTopicPreference::factory()->create(['user_id' => $user->id, 'topic_id' => $topic->id]);
        $cluster = $this->cluster('Resumo tecnológico', now()->subHour(), [$topic]);
        $cluster->update(['summary' => 'Resumo curto.', 'why_it_matters' => 'Importa para o setor.']);
        $digest = DailyDigest::factory()->create([
            'user_id' => $user->id,
            'status' => DailyDigest::STATUS_READY,
            'analyzed_count' => 12,
            'related_count' => 4,
        ]);
        DigestItem::factory()->create([
            'daily_digest_id' => $digest->id,
            'news_cluster_id' => $cluster->id,
            'score' => 88,
            'position' => 1,
        ]);

        (new SendDailyDigestJob($digest->id))->handle();

        $this->assertSame(DailyDigest::STATUS_SENT, $digest->fresh()->status);
        $this->assertNotNull($digest->fresh()->sent_at);
        Mail::assertSent(DailyDigestMail::class, fn (DailyDigestMail $mail): bool => $mail->hasTo($user->email));
        Mail::assertSent(DailyDigestMail::class, fn (DailyDigestMail $mail): bool => str_contains($mail->render(), 'Resumo tecnológico'));
    }

    public function test_empty_digest_is_not_sent(): void
    {
        Mail::fake();
        $digest = DailyDigest::factory()->create(['status' => DailyDigest::STATUS_EMPTY]);

        (new SendDailyDigestJob($digest->id))->handle();

        $this->assertSame(DailyDigest::STATUS_EMPTY, $digest->fresh()->status);
        Mail::assertNothingSent();
    }

    private function cluster(string $title, Carbon $date, array $topics): NewsCluster
    {
        $cluster = NewsCluster::factory()->create([
            'title' => $title,
            'summary' => 'Resumo '.$title,
            'importance_score' => 80,
            'published_at' => $date,
            'last_article_at' => $date,
        ]);
        $article = Article::factory()->create(['published_at' => $date]);
        $article->load('feed.source');
        $article->feed->source->update(['priority' => 50]);
        $article->topics()->attach(collect($topics)->mapWithKeys(fn (Topic $topic): array => [
            $topic->id => ['confidence' => 1, 'detected_by' => 'test'],
        ])->all());
        $cluster->articles()->attach($article->id);

        return $cluster;
    }
}
