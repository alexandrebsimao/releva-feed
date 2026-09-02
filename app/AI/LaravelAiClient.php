<?php

namespace App\AI;

use App\Services\AI\AiMetricRecorder;
use App\Services\AI\AiRequestThrottle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

use function Laravel\Ai\agent;

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
            $this->throttle->wait();
            $response = agent(
                instructions: $instructions,
                schema: fn (JsonSchema $schema): array => StructuredSchemas::forTask($task, $schema),
            )->prompt($prompt, provider: (string) config('ai.provider'), model: $model);

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
            $this->throttle->wait();
            $response = agent(instructions: $instructions)
                ->prompt($prompt, provider: (string) config('ai.provider'), model: $model);

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
}
