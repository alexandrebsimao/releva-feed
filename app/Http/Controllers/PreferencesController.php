<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PreferencesController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        return view('preferences.index', [
            'followedTopicsCount' => $user->topicPreferences()->where('preference', 'follow')->count(),
            'selectedSourcesCount' => $user->sources()->count(),
        ]);
    }
}
