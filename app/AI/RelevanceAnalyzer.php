<?php

namespace App\AI;

use App\Models\Topic;
use RuntimeException;

class RelevanceAnalyzer
{
    public function __construct(
        private readonly AiClient $client,
        private readonly AIResponseValidator $validator,
    ) {}

    /** @param iterable<Topic|string> $topics @return array<string, mixed> */
    public function analyze(string $title, ?string $description, iterable $topics = []): array
    {
        $response = $this->client->structured(
            AiTasks::RELEVANCE_ANALYZER,
            'Analise a relevância da notícia para os assuntos informados e responda somente no formato estruturado solicitado.',
            json_encode([
                'title' => $title,
                'description' => $description,
                'topics' => $this->topicNames($topics),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $this->model(),
        );

        return $this->validator->relevance($response);
    }

    /** @param iterable<Topic|string> $topics @return list<string> */
    private function topicNames(iterable $topics): array
    {
        $names = [];

        foreach ($topics as $topic) {
            $names[] = $topic instanceof Topic ? $topic->name : (string) $topic;
        }

        return array_values(array_unique($names));
    }

    private function model(): string
    {
        $model = trim((string) config('ai.classifier_model'));

        if ($model === '') {
            throw new RuntimeException('Configure AI_CLASSIFIER_MODEL para usar a IA.');
        }

        return $model;
    }
}
