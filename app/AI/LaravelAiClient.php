<?php

namespace App\AI;

use App\Services\AI\AiMetricRecorder;
use App\Services\AI\AiRequestThrottle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

class LaravelAiClient implements AiClient
{
    public function __construct(
        private readonly AiMetricRecorder $metrics,
        private readonly AiRequestThrottle $throttle,
    ) {}

    public function structured(string $task, string $instructions, string $prompt, string $model): array
    {
        $startedAt = hrtime(true);
        $response = null;
        $status = 'success';

        try {
            $response = $this->withTransientRetries(function () use ($instructions, $prompt, $model, $task) {
                $this->throttle->wait();

                return (new ConfiguredStructuredAgent(
                    $instructions,
                    fn (JsonSchema $schema): array => StructuredSchemas::forTask($task, $schema),
                    (int) config("ai.tasks.{$task}.max_tokens", 600),
                    (float) config("ai.tasks.{$task}.temperature", 0.1),
                ))->prompt($prompt, provider: (string) config('ai.provider'), model: $model);
            });

            if (! $response instanceof StructuredAgentResponse) {
                throw new RuntimeException('O provider de IA não retornou uma resposta estruturada.');
            }

            return $response->toArray();
        } catch (Throwable $exception) {
            $status = 'failed';

            throw $exception;
        } finally {
            $usage = $this->metrics->usageTokens($response);
            $this->metrics->record(
                (string) config('ai.provider'),
                $model,
                $task,
                $usage['input'],
                $usage['output'],
                (int) round((hrtime(true) - $startedAt) / 1_000_000),
                $status,
            );
        }
    }

    public function text(string $task, string $instructions, string $prompt, string $model): string
    {
        $startedAt = hrtime(true);
        $response = null;
        $status = 'success';

        try {
            $response = $this->withTransientRetries(function () use ($instructions, $prompt, $model) {
                $this->throttle->wait();

                return (new AnonymousAgent($instructions, [], []))
                    ->prompt($prompt, provider: (string) config('ai.provider'), model: $model);
            });

            return (string) $response->text;
        } catch (Throwable $exception) {
            $status = 'failed';

            throw $exception;
        } finally {
            $usage = $this->metrics->usageTokens($response);
            $this->metrics->record(
                (string) config('ai.provider'),
                $model,
                $task,
                $usage['input'],
                $usage['output'],
                (int) round((hrtime(true) - $startedAt) / 1_000_000),
                $status,
            );
        }
    }

    private function withTransientRetries(callable $callback): mixed
    {
        $maxAttempts = max(1, (int) config('ai.transient_retries', 3));
        $backoff = [30_000, 120_000, 300_000];

        for ($attempt = 1; ; $attempt++) {
            try {
                return $callback();
            } catch (Throwable $exception) {
                if ($attempt >= $maxAttempts || ! $this->isTransient($exception)) {
                    throw $exception;
                }

                usleep($backoff[$attempt - 1] ?? end($backoff));
            }
        }
    }

    private function isTransient(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException
            || $exception instanceof ProviderConnectionException
            || $exception instanceof ProviderOverloadedException
            || $exception instanceof RateLimitedException) {
            return true;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && ($exception->response->serverError() || $exception->response->status() === 429);
    }
}
