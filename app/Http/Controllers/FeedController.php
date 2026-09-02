<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedRequest;
use App\Http\Requests\UpdateFeedRequest;
use App\Models\Feed;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function create(Source $source): View
    {
        return view('feeds.create', ['source' => $source, 'feed' => new Feed(['active' => true])]);
    }

    public function store(StoreFeedRequest $request, Source $source): RedirectResponse
    {
        $source->feeds()->create($request->validated());

        return redirect()->route('sources.edit', $source)->with('status', 'Feed adicionado.');
    }

    public function edit(Source $source, Feed $feed): View
    {
        $this->ensureBelongsToSource($source, $feed);

        return view('feeds.edit', compact('source', 'feed'));
    }

    public function update(UpdateFeedRequest $request, Source $source, Feed $feed): RedirectResponse
    {
        $this->ensureBelongsToSource($source, $feed);
        $feed->update($request->validated());

        return redirect()->route('sources.edit', $source)->with('status', 'Feed atualizado.');
    }

    public function destroy(Source $source, Feed $feed): RedirectResponse
    {
        $this->ensureBelongsToSource($source, $feed);
        $feed->delete();

        return redirect()->route('sources.edit', $source)->with('status', 'Feed removido.');
    }

    private function ensureBelongsToSource(Source $source, Feed $feed): void
    {
        abort_unless($feed->source_id === $source->id, 404);
    }
}
