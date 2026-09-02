<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedRequest;
use App\Http\Requests\UpdateFeedRequest;
use App\Models\Feed;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function create(Source $source): View
    {
        return view('feeds.create', [
            'source' => $source,
            'feed' => new Feed(['active' => true]),
            'sources' => $this->sourceOptions($source),
        ]);
    }

    public function store(StoreFeedRequest $request, Source $source): RedirectResponse
    {
        $data = $request->validated();
        $targetSource = DB::transaction(function () use ($data, $source): Source {
            $targetSource = $this->resolveSource($data, $source);
            $targetSource->feeds()->create($this->feedData($data));

            return $targetSource;
        });

        return redirect()->route('sources.edit', $targetSource)->with('status', 'Feed adicionado.');
    }

    public function edit(Source $source, Feed $feed): View
    {
        $this->ensureBelongsToSource($source, $feed);

        return view('feeds.edit', [
            'source' => $source,
            'feed' => $feed,
            'sources' => $this->sourceOptions($source),
        ]);
    }

    public function update(UpdateFeedRequest $request, Source $source, Feed $feed): RedirectResponse
    {
        $this->ensureBelongsToSource($source, $feed);
        $data = $request->validated();
        $targetSource = DB::transaction(function () use ($data, $source, $feed): Source {
            $targetSource = $this->resolveSource($data, $source);
            $feed->update($this->feedData($data, includeSource: true, sourceId: $targetSource->id));

            return $targetSource;
        });

        return redirect()->route('sources.edit', $targetSource)->with('status', 'Feed atualizado.');
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

    private function sourceOptions(Source $selected): \Illuminate\Support\Collection
    {
        return Source::query()
            ->where(fn ($query) => $query->where('active', true)->orWhereKey($selected->id))
            ->orderBy('name')
            ->get(['id', 'name', 'domain']);
    }

    /** @param array<string, mixed> $data */
    private function resolveSource(array $data, Source $fallback): Source
    {
        if (! empty($data['new_source_name'])) {
            $domain = trim((string) ($data['new_source_domain'] ?? ''));
            $existing = Source::query()->where('domain', $domain)->first();

            if ($existing !== null) {
                return $existing;
            }

            $name = trim((string) $data['new_source_name']);
            $slugBase = Str::slug($name) ?: Str::slug($domain) ?: 'fonte';
            $slug = $slugBase;
            $suffix = 2;

            while (Source::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$suffix;
                $suffix++;
            }

            return Source::create([
                'name' => $name,
                'slug' => $slug,
                'domain' => $domain,
                'priority' => 0,
                'active' => true,
            ]);
        }

        return Source::query()->findOrFail((int) ($data['source_id'] ?? $fallback->id));
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function feedData(array $data, bool $includeSource = false, ?int $sourceId = null): array
    {
        $feedData = collect($data)->only(['name', 'url', 'category', 'active'])->all();

        if ($includeSource) {
            $feedData['source_id'] = $sourceId;
        }

        return $feedData;
    }
}
