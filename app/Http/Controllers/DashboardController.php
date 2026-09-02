<?php

namespace App\Http\Controllers;

use App\Services\News\UserRelevanceCalculator;
use App\Services\Topic\UserTopicPreferenceService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        UserTopicPreferenceService $preferences,
        UserRelevanceCalculator $relevance,
    ): View {
        $articles = $preferences->radarQuery(auth()->user())
            ->with(['feed.source', 'topics', 'clusters'])
            ->orderByDesc('published_at')
            ->orderByDesc('imported_at')
            ->paginate(10);
        $articles->getCollection()->transform(function ($article) use ($relevance) {
            $cluster = $article->clusters->first();
            $scoreSubject = $cluster ?? $article;
            $article->setAttribute('user_relevance_score', $relevance->calculate(auth()->user(), $scoreSubject));

            return $article;
        });

        return view('dashboard', compact('articles'));
    }
}
