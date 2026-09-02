<?php

namespace App\Http\Controllers;

use App\Services\News\HomepageNewsService;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function __invoke(HomepageNewsService $news): View
    {
        return view('public.home', $news->sections());
    }
}
