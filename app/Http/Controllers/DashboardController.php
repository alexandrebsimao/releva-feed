<?php

namespace App\Http\Controllers;

use App\Services\News\UserRelevanceCalculator;
use App\Services\Topic\UserTopicPreferenceService;
use App\Support\SourceSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        UserTopicPreferenceService $preferences,
        UserRelevanceCalculator $relevance,
    ): View {
        $selectedSourceIds = SourceSelection::fromRequest($request);
        $articles = $preferences->radarQuery(auth()->user())
            ->when($selectedSourceIds !== [], fn ($query) => $query->whereHas(
                'feed',
                fn ($feedQuery) => $feedQuery->whereIn('source_id', $selectedSourceIds),
            ))
            ->with(['feed.source', 'topics', 'clusters'])
            ->orderByDesc('published_at')
            ->orderByDesc('imported_at')
            ->paginate(10)
            ->withQueryString();
        $sources = SourceSelection::options();
        $articles->getCollection()->transform(function ($article) use ($relevance) {
            $cluster = $article->clusters->first();
            $scoreSubject = $cluster ?? $article;
            $article->setAttribute('user_relevance_score', $relevance->calculate(auth()->user(), $scoreSubject));

            return $article;
        });

        return view('dashboard', compact('articles', 'sources', 'selectedSourceIds'));
    }
}
