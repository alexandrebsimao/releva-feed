<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSourceRequest;
use App\Http\Requests\UpdateSourceRequest;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SourceController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $termValue = $request->query('q', '');
        $term = is_scalar($termValue) ? trim((string) $termValue) : '';

        $sources = Source::query()
            ->where('active', true)
            ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                $term = mb_strtolower($term, 'UTF-8');
                $search
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw('LOWER(domain) LIKE ?', ['%'.$term.'%']);
            }))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'domain']);

        return response()->json($sources);
    }

    public function index(Request $request): View
    {
        $searchValue = $request->query('q', '');
        $search = is_scalar($searchValue) ? trim((string) $searchValue) : '';
        $userId = auth()->id();

        $selectedSources = Source::query()
            ->whereHas('users', fn ($query) => $query->whereKey($userId))
            ->with(['feeds:id,source_id,name,url,category,active,last_fetched_at,last_success_at,failure_count,last_error'])
            ->withCount('feeds')
            ->withMax('feeds', 'last_fetched_at')
            ->withMax('feeds', 'last_success_at')
            ->withSum('feeds', 'failure_count')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        $availableSources = Source::query()
            ->whereDoesntHave('users', fn ($query) => $query->whereKey($userId))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $normalized = mb_strtolower($search, 'UTF-8');
                $searchQuery
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])
                    ->orWhereRaw('LOWER(domain) LIKE ?', ['%'.$normalized.'%']);
            }))
            ->with(['feeds:id,source_id,name,url,category,active'])
            ->withCount('feeds')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        return view('sources.index', compact('selectedSources', 'availableSources', 'search'));
    }

    public function select(Source $source): RedirectResponse
    {
        abort_unless($source->active, 404);

        auth()->user()->sources()->syncWithoutDetaching([$source->id]);

        return redirect()->route('sources.index')->with('status', 'Fonte adicionada à sua lista.');
    }

    public function deselect(Source $source): RedirectResponse
    {
        auth()->user()->sources()->detach($source->id);

        return redirect()->route('sources.index')->with('status', 'Fonte removida da sua lista.');
    }

    public function create(): View
    {
        return view('sources.create', ['source' => new Source(['active' => true])]);
    }

    public function store(StoreSourceRequest $request): RedirectResponse
    {
        $source = DB::transaction(function () use ($request): Source {
            $source = Source::create($request->validated());
            auth()->user()->sources()->syncWithoutDetaching([$source->id]);

            return $source;
        });

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
        abort_unless(auth()->user()?->is_admin === true, 403);

        $source->delete();

        return redirect()->route('sources.index')->with('status', 'Fonte removida.');
    }
}
