<?php

namespace App\AI;

use App\Services\Topic\TopicNormalizer;

class TopicCandidateMerger
{
    public function __construct(private readonly TopicNormalizer $normalizer) {}

    /** @param list<array<string, mixed>> ...$candidates @return list<array<string, mixed>> */
    public function merge(array ...$candidates): array
    {
        $merged = [];

        foreach ($candidates as $group) {
            foreach ($group as $candidate) {
                $key = $this->normalizer->normalize((string) $candidate['name']);

                if (! isset($merged[$key]) || $candidate['confidence'] > $merged[$key]['confidence']) {
                    $merged[$key] = $candidate;
                }
            }
        }

        return array_values($merged);
    }
}
