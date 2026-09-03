<?php

namespace App\Support;

use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategorySelection
{
    /** @return list<array{value: string, label: string, feed_categories: list<string>}> */
    public static function options(): array
    {
        $feedCategories = Feed::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn (string $category): string => trim($category))
            ->filter()
            ->values();

        $configured = collect(config('topics.editorial_categories', []));
        $options = [];

        foreach ($configured as $category) {
            $aliases = collect($category['feed_categories'] ?? [])
                ->merge($category['aliases'] ?? [])
                ->map(fn (string $value): string => trim($value))
                ->filter()
                ->values();
            $matching = $feedCategories->filter(fn (string $value): bool => $aliases->contains($value))->values()->all();

            if ($matching !== []) {
                $options[] = [
                    'value' => (string) $category['slug'],
                    'label' => (string) $category['name'],
                    'feed_categories' => $matching,
                ];
            }
        }

        $configuredValues = collect($options)->flatMap(fn (array $option): array => $option['feed_categories'])->all();

        foreach ($feedCategories as $feedCategory) {
            if (in_array($feedCategory, $configuredValues, true)) {
                continue;
            }

            $options[] = [
                'value' => $feedCategory,
                'label' => Str::headline($feedCategory),
                'feed_categories' => [$feedCategory],
            ];
        }

        return collect($options)->sortBy('label')->values()->all();
    }

    /** @param list<array{value: string, label: string, feed_categories: list<string>}>|null $options */
    public static function fromRequest(Request $request, ?array $options = null): ?string
    {
        $value = $request->query('category');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $options ??= self::options();

        return collect($options)->contains(fn (array $option): bool => $option['value'] === $value)
            ? $value
            : null;
    }

    /** @param list<array{value: string, label: string, feed_categories: list<string>}> $options @return list<string> */
    public static function feedValues(string $selected, array $options): array
    {
        return collect($options)->firstWhere('value', $selected)['feed_categories'] ?? [];
    }
}
