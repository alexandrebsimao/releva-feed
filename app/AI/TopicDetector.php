<?php

namespace App\AI;

use App\Models\Topic;
use RuntimeException;

class TopicDetector
{
    public function __construct(
        private readonly AiClient $client,
        private readonly AIResponseValidator $validator,
    ) {}

    /** @param iterable<Topic|string> $existingTopics @return array<string, mixed> */
    public function detect(string $text, iterable $existingTopics = []): array
    {
        $response = $this->client->structured(
            AiTasks::TOPIC_DETECTOR,
            'Identifique os assuntos explícitos do texto. Responda somente no formato estruturado solicitado.',
            $this->prompt($text, $existingTopics),
            $this->model(),
        );

        return $this->validator->topics($response);
    }

    /** @param iterable<Topic|string> $existingTopics */
    private function prompt(string $text, iterable $existingTopics): string
    {
        return json_encode([
            'text' => $text,
            'existing_topics' => $this->topicNames($existingTopics),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
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
        return $this->requiredModel('AI_CLASSIFIER_MODEL');
    }

    private function requiredModel(string $variable): string
    {
        $model = trim((string) config('ai.classifier_model'));

        if ($model === '') {
            throw new RuntimeException("Configure {$variable} para usar a IA.");
        }

        return $model;
    }
}
