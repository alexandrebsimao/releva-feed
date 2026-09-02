<?php

namespace App\Support;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SourceSelection
{
    /** @return list<int> */
    public static function fromRequest(Request $request): array
    {
        $values = $request->query('sources', []);
        $values = is_array($values) ? $values : [$values];

        $requestedIds = collect($values)
            ->filter(fn ($value): bool => is_scalar($value) && filter_var($value, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return [];
        }

        return Source::query()
            ->whereIn('id', $requestedIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /** @return Collection<int, Source> */
    public static function options(): Collection
    {
        return Source::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'domain']);
    }
}
