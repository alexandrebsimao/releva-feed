<?php

namespace App\AI;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;

final class StructuredSchemas
{
    /** @return array<string, mixed> */
    public static function forTask(string $task, JsonSchema $schema): array
    {
        $topic = fn ($schema): mixed => $schema->object(fn ($schema): array => [
            'name' => $schema->string()->required(),
            'type' => $schema->string()->enum(['topic', 'company', 'location', 'technology', 'person', 'product'])->required(),
            'confidence' => $schema->number()->min(0)->max(1)->required(),
        ]);

        return match ($task) {
            AiTasks::TOPIC_DETECTOR => [
                'topics' => $schema->array()->items($topic($schema))->required(),
            ],
            AiTasks::ARTICLE_CLASSIFIER => [
                'topics' => $schema->array()->items($topic($schema))->required(),
                'importance' => $schema->integer()->min(0)->max(100)->required(),
                'is_news' => $schema->boolean()->required(),
                'language' => $schema->string()->required(),
            ],
            AiTasks::RELEVANCE_ANALYZER => [
                'is_relevant' => $schema->boolean()->required(),
                'score' => $schema->integer()->min(0)->max(100)->required(),
                'reason' => $schema->string()->required(),
            ],
            AiTasks::NEWS_SUMMARIZER => [
                'title' => $schema->string()->required(),
                'summary' => $schema->string()->required(),
                'why_it_matters' => $schema->string()->required(),
                'importance' => $schema->integer()->min(0)->max(100)->required(),
            ],
            default => throw new InvalidArgumentException("Task de IA sem schema: {$task}"),
        };
    }

    private function __construct() {}
}
