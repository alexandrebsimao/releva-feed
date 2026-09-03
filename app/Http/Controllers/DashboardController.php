<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\News\UserRelevanceCalculator;
use App\Services\Topic\UserTopicPreferenceService;
use App\Support\CategorySelection;
use App\Support\SourceSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        UserTopicPreferenceService $preferences,
        UserRelevanceCalculator $relevance,
    ): View {
        return $this->feed($request, $preferences, $relevance, $this->mode($request));
    }

    public function sources(
        Request $request,
        UserTopicPreferenceService $preferences,
        UserRelevanceCalculator $relevance,
    ): View {
        return $this->feed($request, $preferences, $relevance, 'sources');
    }

    private function feed(
        Request $request,
        UserTopicPreferenceService $preferences,
        UserRelevanceCalculator $relevance,
        string $mode,
    ): View {
        $articles = match ($mode) {
            'all' => Article::query(),
            'sources' => $this->sourcesQuery(),
            default => $preferences->radarQuery(auth()->user()),
        };

        $selectedSourceIds = SourceSelection::fromRequest($request);
        $categoryOptions = CategorySelection::options();
        $selectedCategory = CategorySelection::fromRequest($request, $categoryOptions);
        $articles->when(
            $selectedSourceIds !== [],
            fn ($query) => $query->whereHas('feed', fn ($feedQuery) => $feedQuery->whereIn('source_id', $selectedSourceIds)),
        );
        $articles->when(
            $selectedCategory !== null,
            fn ($query) => $query->whereHas(
                'feed',
                fn ($feedQuery) => $feedQuery->whereIn('category', CategorySelection::feedValues($selectedCategory, $categoryOptions)),
            ),
        );

        $articles = $articles
            ->with(['feed.source', 'topics', 'clusters.articles.feed.source', 'clusters.articles.topics'])
            ->orderByDesc('published_at')
            ->orderByDesc('imported_at')
            ->paginate(10)
            ->withQueryString();

        if ($mode === 'interests') {
            $articles->getCollection()->transform(function ($article) use ($relevance) {
                $cluster = $article->clusters->first();
                $scoreSubject = $cluster ?? $article;
                $article->setAttribute('user_relevance_score', $relevance->calculate(auth()->user(), $scoreSubject));

                return $article;
            });
        }

        return view('dashboard', [
            'articles' => $articles,
            'mode' => $mode,
            'sources' => SourceSelection::options(),
            'selectedSourceIds' => $selectedSourceIds,
            'categories' => $categoryOptions,
            'selectedCategory' => $selectedCategory,
            'selectedSourceCount' => auth()->user()->sources()->where('sources.active', true)->count(),
        ]);
    }

    private function mode(Request $request): string
    {
        $mode = $request->query('view', 'interests');

        return is_string($mode) && in_array($mode, ['all', 'sources', 'interests'], true)
            ? $mode
            : 'interests';
    }

    private function sourcesQuery(): Builder
    {
        $sourceIds = auth()->user()->sources()
            ->where('sources.active', true)
            ->pluck('sources.id');

        return Article::query()->when(
            $sourceIds->isEmpty(),
            fn ($query) => $query->whereRaw('1 = 0'),
            fn ($query) => $query->whereHas('feed', fn ($feedQuery) => $feedQuery->whereIn('source_id', $sourceIds)),
        );
    }
}
