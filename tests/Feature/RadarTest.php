<?php

namespace Tests\Feature;

use App\Livewire\Radar;
use App\Models\Article;
use App\Models\NewsCluster;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RadarTest extends TestCase
{
    use RefreshDatabase;

    public function test_radar_requires_authentication(): void
    {
        $this->get('/radar')->assertRedirect('/login');
    }

    public function test_all_shows_only_relevant_clusters_grouped_by_day_and_in_reverse_chronological_order(): void
    {
        $user = User::factory()->create();
        $toyota = Topic::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $economy = Topic::factory()->create(['name' => 'Economia', 'slug' => 'economia']);
        $excluded = Topic::factory()->create(['name' => 'Esportes', 'slug' => 'esportes']);
        $this->follow($user, $toyota, UserTopicPreference::PRIORITY_HIGH);
        $this->follow($user, $economy, UserTopicPreference::PRIORITY_NORMAL);
        $this->exclude($user, $excluded);

        $today = $this->cluster('Toyota anuncia novo SUV', now()->setTime(9, 42), [$toyota]);
        $yesterday = $this->cluster('Economia brasileira em destaque', now()->subDay()->setTime(15, 10), [$economy]);
        $old = $this->cluster('Toyota apresenta tecnologia', now()->subDays(3)->setTime(11, 0), [$toyota]);
        $hidden = $this->cluster('Notícia esportiva bloqueada', now()->setTime(10, 0), [$excluded]);

        $test = Livewire::actingAs($user)->test(Radar::class);

        $test->assertSee('Todos')
            ->assertSee('Toyota')
            ->assertSee('Economia')
            ->assertSee('+ Assuntos')
            ->assertSee('Toyota anuncia novo SUV')
            ->assertSee('Economia brasileira em destaque')
            ->assertSee('Toyota apresenta tecnologia')
            ->assertDontSee('Notícia esportiva bloqueada')
            ->assertSee('Hoje')
            ->assertSee('Ontem');

        $dateLabel = Carbon::parse($old->last_article_at)->locale('pt_BR')->translatedFormat('j \\d\\e F');
        $test->assertSee($dateLabel);
        $html = $test->html();
        $this->assertLessThan(strpos($html, 'Economia'), strpos($html, 'Toyota'));
        $this->assertLessThan(strpos($html, 'Toyota apresenta tecnologia'), strpos($html, 'Economia brasileira em destaque'));
        $this->assertLessThan(strpos($html, 'Economia brasileira em destaque'), strpos($html, 'Toyota anuncia novo SUV'));
    }

    public function test_topic_filter_updates_without_reload_and_is_kept_in_query_state(): void
    {
        $user = User::factory()->create();
        $toyota = Topic::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $economy = Topic::factory()->create(['name' => 'Economia', 'slug' => 'economia']);
        $this->follow($user, $toyota, UserTopicPreference::PRIORITY_HIGH);
        $this->follow($user, $economy, UserTopicPreference::PRIORITY_LOW);
        $this->cluster('Acontecimento Toyota', now(), [$toyota]);
        $this->cluster('Acontecimento Economia', now()->subMinutes(5), [$economy]);

        Livewire::actingAs($user)
            ->test(Radar::class)
            ->assertSet('topic', '')
            ->set('topic', 'toyota')
            ->assertSet('topic', 'toyota')
            ->assertSee('Acontecimento Toyota')
            ->assertDontSee('Acontecimento Economia');

        $this->actingAs($user)
            ->get('/radar?topic=toyota')
            ->assertOk()
            ->assertSee('Acontecimento Toyota')
            ->assertDontSee('Acontecimento Economia');
    }

    public function test_excluded_topic_removes_a_cluster_even_when_another_topic_is_followed(): void
    {
        $user = User::factory()->create();
        $followed = Topic::factory()->create(['name' => 'Tecnologia']);
        $excluded = Topic::factory()->create(['name' => 'Inteligência Artificial']);
        $this->follow($user, $followed);
        $this->exclude($user, $excluded);
        $blocked = $this->cluster('Tecnologia e IA bloqueadas', now(), [$followed, $excluded]);
        $allowed = $this->cluster('Somente tecnologia', now()->subHour(), [$followed]);

        Livewire::actingAs($user)
            ->test(Radar::class)
            ->assertSee($allowed->title)
            ->assertDontSee($blocked->title);
    }

    public function test_radar_paginates_clusters_without_loading_the_whole_history(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['name' => 'Tecnologia']);
        $this->follow($user, $topic);
        foreach (range(0, 10) as $index) {
            $this->cluster('Cluster paginado '.$index, now()->subMinutes($index), [$topic]);
        }

        $test = Livewire::actingAs($user)->test(Radar::class)
            ->assertSee('Cluster paginado 0')
            ->assertDontSee('Cluster paginado 10');

        $test->call('nextPage')
            ->assertSee('Cluster paginado 10');
    }

    private function cluster(string $title, Carbon $date, array $topics): NewsCluster
    {
        $cluster = NewsCluster::factory()->create([
            'title' => $title,
            'summary' => 'Resumo de '.$title,
            'why_it_matters' => 'Importância de '.$title,
            'published_at' => $date,
            'last_article_at' => $date,
        ]);
        $article = Article::factory()->create([
            'original_title' => $title,
            'published_at' => $date,
        ]);
        $article->topics()->attach(collect($topics)->mapWithKeys(fn (Topic $topic): array => [
            $topic->id => ['confidence' => 1, 'detected_by' => 'test'],
        ])->all());
        $cluster->articles()->attach($article);

        return $cluster;
    }

    private function follow(User $user, Topic $topic, int $priority = UserTopicPreference::PRIORITY_NORMAL): void
    {
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_FOLLOW,
            'priority' => $priority,
        ]);
    }

    private function exclude(User $user, Topic $topic): void
    {
        UserTopicPreference::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'preference' => UserTopicPreference::PREFERENCE_EXCLUDE,
        ]);
    }
}
