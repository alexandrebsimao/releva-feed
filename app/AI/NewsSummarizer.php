<?php

namespace App\AI;

use App\Models\Article;
use App\Models\NewsCluster;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class NewsSummarizer
{
    public function __construct(
        private readonly AiClient $client,
        private readonly AIResponseValidator $validator,
    ) {}

    /**
     * @param  iterable<Article|array<string, mixed>>  $articles
     * @return array{title: string, summary: string, why_it_matters: string, importance: int}
     */
    public function summarize(iterable $articles): array
    {
        $articles = collect($articles);

        if ($articles->isEmpty()) {
            throw new RuntimeException('Não é possível resumir um acontecimento sem artigos.');
        }

        $response = $this->client->structured(
            AiTasks::NEWS_SUMMARIZER,
            <<<'INSTRUCTIONS'
Você consolida matérias jornalísticas em português do Brasil. Use exclusivamente os fatos presentes nos artigos fornecidos. Não invente fatos, fontes, datas ou entidades. Se houver divergência relevante entre as fontes, preserve-a de forma objetiva no resumo. Gere um título objetivo, um resumo curto, uma explicação curta de por que o acontecimento importa e uma importância de 0 a 100. Responda somente no formato estruturado solicitado.
INSTRUCTIONS,
            $this->prompt($articles),
            $this->model(),
        );

        return $this->validator->summary($response);
    }

    /**
     * Resume e persiste apenas clusters ainda não resumidos ou invalidados por nova matéria.
     *
     * @return array{title: string, summary: string, why_it_matters: string, importance: int}
     */
    public function summarizeCluster(NewsCluster $cluster): array
    {
        $cluster->loadMissing('articles.feed.source');

        if (! $this->needsProcessing($cluster)) {
            return [
                'title' => $cluster->title,
                'summary' => $cluster->summary,
                'why_it_matters' => $cluster->why_it_matters,
                'importance' => $cluster->importance_score,
            ];
        }

        $summary = $this->summarize($cluster->articles);
        $cluster->update([
            'title' => $summary['title'],
            'summary' => $summary['summary'],
            'why_it_matters' => $summary['why_it_matters'],
            'importance_score' => $summary['importance'],
        ]);

        return $summary;
    }

    public function needsProcessing(NewsCluster $cluster): bool
    {
        return trim((string) $cluster->summary) === ''
            || trim((string) $cluster->why_it_matters) === ''
            || $cluster->importance_score === null;
    }

    /** @param Collection<int, Article|array<string, mixed>> $articles */
    private function prompt(Collection $articles): string
    {
        return json_encode([
            'articles' => $articles->map(fn (Article|array $article): array => $article instanceof Article
                ? $this->articlePayload($article)
                : $this->arrayPayload($article))->values()->all(),
            'output_contract' => [
                'title' => 'título objetivo em português do Brasil',
                'summary' => 'resumo factual curto em português do Brasil',
                'why_it_matters' => 'explicação curta e factual em português do Brasil',
                'importance' => 'inteiro entre 0 e 100',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return array{source: string, original_title: string, description: ?string, date: ?string} */
    private function articlePayload(Article $article): array
    {
        $article->loadMissing('feed.source');
        $source = $article->feed?->source?->name;

        if (! is_string($source) || trim($source) === '') {
            throw new RuntimeException('Todo artigo precisa ter uma fonte identificável para ser resumido.');
        }

        return [
            'source' => $source,
            'original_title' => $article->original_title,
            'description' => $article->description,
            'date' => $article->published_at instanceof CarbonInterface
                ? $article->published_at->toIso8601String()
                : null,
        ];
    }

    /** @param array<string, mixed> $article @return array{source: string, original_title: string, description: ?string, date: ?string} */
    private function arrayPayload(array $article): array
    {
        $source = $article['source'] ?? null;
        $title = $article['original_title'] ?? null;

        if (! is_string($source) || trim($source) === '' || ! is_string($title) || trim($title) === '') {
            throw new RuntimeException('Cada artigo precisa ter fonte e título para ser resumido.');
        }

        return [
            'source' => $source,
            'original_title' => $title,
            'description' => isset($article['description']) ? (string) $article['description'] : null,
            'date' => isset($article['date']) ? (string) $article['date'] : null,
        ];
    }

    private function model(): string
    {
        $model = trim((string) config('ai.summarizer_model'));

        if ($model === '') {
            throw new RuntimeException('Configure AI_SUMMARIZER_MODEL para usar a IA.');
        }

        return $model;
    }
}
