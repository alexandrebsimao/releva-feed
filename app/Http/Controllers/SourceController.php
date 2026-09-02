<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSourceRequest;
use App\Http\Requests\UpdateSourceRequest;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SourceController extends Controller
{
    public function index(): View
    {
        $sources = Source::query()
            ->with(['feeds:id,source_id,active,last_fetched_at,last_success_at,failure_count,last_error'])
            ->withCount('feeds')
            ->withMax('feeds', 'last_fetched_at')
            ->withMax('feeds', 'last_success_at')
            ->withSum('feeds', 'failure_count')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15);

        return view('sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('sources.create', ['source' => new Source(['active' => true])]);
    }

    public function store(StoreSourceRequest $request): RedirectResponse
    {
        $source = Source::create($request->validated());

        return redirect()->route('sources.edit', $source)->with('status', 'Fonte criada. Agora adicione os feeds.');
    }

    public function edit(Source $source): View
    {
        $source->load(['feeds' => fn ($query) => $query->orderBy('name')]);

        return view('sources.edit', compact('source'));
    }

    public function update(UpdateSourceRequest $request, Source $source): RedirectResponse
    {
        $source->update($request->validated());

        return redirect()->route('sources.edit', $source)->with('status', 'Fonte atualizada.');
    }

    public function destroy(Source $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('sources.index')->with('status', 'Fonte removida.');
    }
}
