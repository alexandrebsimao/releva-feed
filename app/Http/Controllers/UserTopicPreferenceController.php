<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserTopicPreferenceRequest;
use App\Models\Topic;
use App\Models\UserTopicPreference;
use App\Models\UserTopicSuggestion;
use App\Services\Topic\UserTopicDiscoveryService;
use App\Services\Topic\UserTopicPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserTopicPreferenceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $user = Auth::user();
        $preferences = $user->topicPreferences()->with('topic')->get();
        $preferredTopicIds = $preferences->pluck('topic_id')->all();
        $normalizedSearch = Str::lower($search);

        $availableTopics = Topic::query()
            ->where('active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($normalizedSearch): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$normalizedSearch.'%'])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ['%'.$normalizedSearch.'%']);
            }))
            ->whereNotIn('id', $preferredTopicIds)
            ->orderBy('name')
            ->get();

        return view('my-topics.index', [
            'followedPreferences' => $preferences
                ->where('preference', UserTopicPreference::PREFERENCE_FOLLOW)
                ->sortByDesc('priority')
                ->values(),
            'excludedPreferences' => $preferences
                ->where('preference', UserTopicPreference::PREFERENCE_EXCLUDE)
                ->sortByDesc('priority')
                ->values(),
            'availableTopics' => $availableTopics,
            'search' => $search,
            'suggestions' => $user->topicSuggestions()
                ->whereNull('ignored_at')
                ->whereNotIn('topic_id', $preferredTopicIds)
                ->with('topic')
                ->orderByDesc('article_count')
                ->get(),
            'priorityLabels' => [
                UserTopicPreference::PRIORITY_LOW => 'Baixa',
                UserTopicPreference::PRIORITY_NORMAL => 'Normal',
                UserTopicPreference::PRIORITY_HIGH => 'Alta',
            ],
        ]);
    }

    public function follow(Topic $topic, UserTopicPreferenceService $preferences): RedirectResponse
    {
        $preferences->setPreference(Auth::user(), $topic, UserTopicPreference::PREFERENCE_FOLLOW);

        return $this->backWithStatus('Assunto adicionado ao acompanhamento.');
    }

    public function exclude(Topic $topic, UserTopicPreferenceService $preferences): RedirectResponse
    {
        $preferences->setPreference(Auth::user(), $topic, UserTopicPreference::PREFERENCE_EXCLUDE);

        return $this->backWithStatus('Assunto bloqueado no seu radar.');
    }

    public function update(
        UpdateUserTopicPreferenceRequest $request,
        Topic $topic,
        UserTopicPreferenceService $preferences,
    ): RedirectResponse {
        $preferences->updatePriority(Auth::user(), $topic, (int) $request->validated('priority'));

        return $this->backWithStatus('Prioridade atualizada.');
    }

    public function destroy(Topic $topic, UserTopicPreferenceService $preferences): RedirectResponse
    {
        $preferences->removePreference(Auth::user(), $topic);

        return $this->backWithStatus('Preferência removida.');
    }

    public function ignoreSuggestion(
        UserTopicSuggestion $suggestion,
        UserTopicDiscoveryService $discovery,
    ): RedirectResponse {
        $discovery->ignore(Auth::user(), $suggestion);

        return $this->backWithStatus('Sugestão ignorada.');
    }

    private function backWithStatus(string $message): RedirectResponse
    {
        return redirect()->route('my-topics.index', request()->only('q'))->with('status', $message);
    }
}
