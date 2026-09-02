<?php

namespace App\Livewire;

use App\Models\NewsCluster;
use App\Models\UserTopicPreference;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.authenticated')]
class Radar extends Component
{
    use WithPagination;

    #[Url(as: 'topic', except: '')]
    public string $topic = '';

    public function selectTopic(string $topic = ''): void
    {
        $this->topic = $topic;
        $this->resetPage();
    }

    public function render(): View
    {
        $preferences = auth()->user()->topicPreferences()->with('topic')->get();
        $followedPreferences = $preferences
            ->where('preference', UserTopicPreference::PREFERENCE_FOLLOW)
            ->sortByDesc(fn (UserTopicPreference $preference): array => [$preference->priority, $preference->topic->name])
            ->values();
        $followedTopicIds = $followedPreferences->pluck('topic_id')->all();
        $excludedTopicIds = $preferences
            ->where('preference', UserTopicPreference::PREFERENCE_EXCLUDE)
            ->pluck('topic_id')
            ->all();
        $selectedTopic = $followedPreferences->first(
            fn (UserTopicPreference $preference): bool => $preference->topic->slug === $this->topic,
        );

        $clusters = NewsCluster::query()
            ->whereIn('public_status', [NewsCluster::STATUS_AUTOMATIC, NewsCluster::STATUS_FEATURED])
            ->when(
                $followedTopicIds === [],
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                fn (Builder $query): Builder => $query->whereHas(
                    'articles.topics',
                    fn (Builder $topicQuery): Builder => $topicQuery->whereIn('topics.id', $followedTopicIds),
                ),
            )
            ->whereDoesntHave(
                'articles.topics',
                fn (Builder $query): Builder => $query->whereIn('topics.id', $excludedTopicIds),
            )
            ->when(
                $this->topic !== '' && $selectedTopic === null,
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->when(
                $selectedTopic !== null,
                function (Builder $query) use ($selectedTopic): Builder {
                    return $query->whereHas(
                        'articles.topics',
                        fn (Builder $topicQuery): Builder => $topicQuery->where('topics.id', $selectedTopic->topic_id),
                    );
                },
            )
            ->with(['articles.feed.source', 'articles.topics'])
            ->orderByDesc('last_article_at')
            ->orderByDesc('id')
            ->paginate(10);

        $groupedClusters = $clusters->getCollection()->groupBy(
            fn (NewsCluster $cluster): string => $cluster->last_article_at->toDateString(),
        );
        $dayLabels = $groupedClusters->keys()->mapWithKeys(
            fn (string $day): array => [$day => $this->dayLabel(Carbon::parse($day))],
        )->all();

        return view('livewire.radar', compact('clusters', 'followedPreferences', 'groupedClusters', 'dayLabels'));
    }

    public function recencyLabel(CarbonInterface $date): string
    {
        $minutes = max(0, $date->diffInMinutes(now()));

        if ($minutes < 1) {
            return 'agora';
        }

        if ($minutes < 60) {
            return 'há '.$minutes.' min';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return 'há '.$hours.' h';
        }

        return 'há '.intdiv($hours, 24).' d';
    }

    private function dayLabel(CarbonInterface $date): string
    {
        $today = now()->startOfDay();

        if ($date->isSameDay($today)) {
            return 'Hoje';
        }

        if ($date->isSameDay($today->copy()->subDay())) {
            return 'Ontem';
        }

        return $date->locale('pt_BR')->translatedFormat('j \\d\\e F');
    }
}
