<?php

namespace App\Http\Controllers;

use App\Services\News\HomepageNewsService;
use App\Support\SourceSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function __invoke(Request $request, HomepageNewsService $news): View
    {
        $selectedSourceIds = SourceSelection::fromRequest($request);
        $sources = SourceSelection::options();

        return view('public.home', [
            ...$news->sections($selectedSourceIds),
            'sources' => $sources,
            'selectedSourceIds' => $selectedSourceIds,
        ]);
    }
}
