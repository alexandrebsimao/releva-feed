<?php

namespace App\Services\Topic;

use Illuminate\Support\Str;

class TopicNormalizer
{
    public function normalize(string $value): string
    {
        $value = Str::ascii($value);
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /** @return list<string> */
    public function splitAliases(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $alias) => trim($alias))
            ->filter(fn (string $alias) => $this->normalize($alias) !== '')
            ->unique(fn (string $alias) => $this->normalize($alias))
            ->values()
            ->all();
    }
}
