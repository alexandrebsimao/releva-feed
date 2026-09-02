<?php

namespace App\Http\Controllers;

use App\Models\NewsCluster;
use App\Support\SourceSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsClusterController extends Controller
{
    public function show(Request $request, NewsCluster $newsCluster): View
    {
        if ($newsCluster->public_status === NewsCluster::STATUS_HIDDEN && ! auth()->check()) {
            abort(404);
        }

        $selectedSourceIds = SourceSelection::fromRequest($request);
        $newsCluster->load([
            'articles' => fn ($query) => $query
                ->when($selectedSourceIds !== [], fn ($articleQuery) => $articleQuery->whereHas(
                    'feed',
                    fn ($feedQuery) => $feedQuery->whereIn('source_id', $selectedSourceIds),
                ))
                ->with(['feed.source', 'topics'])
                ->orderByDesc('published_at'),
        ]);

        abort_if($selectedSourceIds !== [] && $newsCluster->articles->isEmpty(), 404);

        $topics = $newsCluster->articles
            ->flatMap(fn ($article) => $article->topics)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $sourceCount = $newsCluster->articles
            ->map(fn ($article) => $article->feed?->source?->id)
            ->filter()
            ->unique()
            ->count();

        return view('news.show', compact('newsCluster', 'topics', 'sourceCount', 'selectedSourceIds'));
    }
}
