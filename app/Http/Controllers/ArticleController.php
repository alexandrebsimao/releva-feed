<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\SourceSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $selectedSourceIds = SourceSelection::fromRequest($request);
        $articles = Article::query()
            ->with(['feed.source', 'topics'])
            ->when($selectedSourceIds !== [], fn ($query) => $query->whereHas(
                'feed',
                fn ($feedQuery) => $feedQuery->whereIn('source_id', $selectedSourceIds),
            ))
            ->orderByDesc('published_at')
            ->orderByDesc('imported_at')
            ->paginate(20)
            ->withQueryString();
        $sources = SourceSelection::options();

        return view('articles.index', compact('articles', 'sources', 'selectedSourceIds'));
    }
}
