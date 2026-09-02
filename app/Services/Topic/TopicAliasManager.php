<?php

namespace App\Services\Topic;

use App\Models\Topic;

class TopicAliasManager
{
    public function __construct(private readonly TopicNormalizer $normalizer) {}

    public function sync(Topic $topic, ?string $aliases): void
    {
        $normalizedAliases = collect($this->normalizer->splitAliases($aliases))
            ->mapWithKeys(fn (string $alias) => [$this->normalizer->normalize($alias) => $alias]);

        if ($normalizedAliases->isEmpty()) {
            $topic->aliases()->delete();

            return;
        }

        $topic->aliases()
            ->whereNotIn('normalized_name', $normalizedAliases->keys()->all())
            ->delete();

        foreach ($normalizedAliases as $normalizedName => $name) {
            $topic->aliases()->updateOrCreate(
                ['normalized_name' => $normalizedName],
                ['name' => $name],
            );
        }
    }
}
