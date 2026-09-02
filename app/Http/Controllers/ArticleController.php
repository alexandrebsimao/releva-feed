<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->with(['feed.source'])
            ->orderByDesc('published_at')
            ->orderByDesc('imported_at')
            ->paginate(20);

        return view('articles.index', compact('articles'));
    }
}
