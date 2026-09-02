<?php

namespace Tests\Unit;

use App\Services\Topic\TopicNormalizer;
use Tests\TestCase;

class TopicNormalizerTest extends TestCase
{
    public function test_it_normalizes_accents_case_punctuation_and_whitespace(): void
    {
        $normalizer = app(TopicNormalizer::class);

        $this->assertSame('inteligencia artificial', $normalizer->normalize('  Inteligência   Artificial! '));
    }

    public function test_it_deduplicates_aliases_by_normalized_name(): void
    {
        $aliases = app(TopicNormalizer::class)->splitAliases("IA\n ia \nA.I.\nAI");

        $this->assertSame(['IA', 'A.I.', 'AI'], $aliases);
    }
}
