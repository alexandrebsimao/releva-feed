<?php

namespace App\AI;

use App\Models\Article;
use App\Models\Source;
use App\Models\Topic;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use RuntimeException;

class ArticleClassifier
{
    public function __construct(
        private readonly AiClient $client,
        private readonly AIResponseValidator $validator,
        private readonly TopicCatalog $catalog,
    ) {}

    /** @param iterable<Topic|string> $existingTopics @return array<string, mixed> */
    public function classify(
        string $title,
        ?string $description,
        string|Source $source,
        CarbonInterface|string $date,
        iterable $existingTopics = [],
    ): array {
        $response = $this->client->structured(
            AiTasks::ARTICLE_CLASSIFIER,
            'Classifique a notícia. Use somente fatos explícitos no conteúdo. Prefira os assuntos existentes quando houver correspondência, retorne no máximo 3 assuntos relevantes e não invente fatos. Responda somente no formato estruturado solicitado.',
            $this->prompt($title, $description, $source, $date, $existingTopics),
            $this->model(),
        );

        return $this->validator->classification($response);
    }

    /** Classifica e só então persiste os assuntos validados na pivot do artigo. */
    public function classifyAndPersist(Article $article, iterable $existingTopics = []): array
    {
        $article->loadMissing('feed.source');
        $topics = $existingTopics === [] ? Topic::query()->where('active', true)->get() : $existingTopics;
        $classification = $this->classify(
            $article->original_title,
            $article->description,
            $article->feed->source->name,
            $article->published_at,
            $topics,
        );

        $this->catalog->attachToArticle($article, $classification['topics']);
        $article->update([
            'ai_classification' => $classification,
            'ai_classified_at' => now(),
            'ai_last_error' => null,
        ]);

        return $classification;
    }

    /** @param iterable<Topic|string> $existingTopics */
    private function prompt(
        string $title,
        ?string $description,
        string|Source $source,
        CarbonInterface|string $date,
        iterable $existingTopics,
    ): string {
        return json_encode([
            'title' => $title,
            'description' => $this->excerpt($description, (int) config('ai.article_description_max_chars', 6000)),
            'source' => $source instanceof Source ? $source->name : $source,
            'date' => $date instanceof CarbonInterface ? $date->toIso8601String() : $date,
            'existing_relevant_topics' => $this->topicNames($existingTopics),
            'output_contract' => [
                'topics' => [['name' => 'string', 'type' => 'topic|company|location|technology|person|product', 'confidence' => '0..1 (no máximo 3)']],
                'importance' => 'integer 0..100',
                'is_news' => 'boolean',
                'language' => 'BCP-47 language tag',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @param iterable<Topic|string> $topics @return list<string> */
    private function topicNames(iterable $topics): array
    {
        $names = [];

        foreach ($topics as $topic) {
            $names[] = $topic instanceof Topic ? $topic->name : (string) $topic;
        }

        return array_slice(array_values(array_unique($names)), 0, max(1, (int) config('ai.max_existing_topics', 100)));
    }

    private function excerpt(?string $text, int $limit): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        return $text === '' ? null : Str::limit($text, max(100, $limit), '…');
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
