<?php

namespace App\Services\AI;

use App\Models\AiMetric;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiMetricRecorder
{
    /** @return array{input: ?int, output: ?int} */
    public function usageTokens(mixed $response): array
    {
        $usage = is_object($response) ? ($response->usage ?? null) : null;

        if (is_object($usage)) {
            return [
                'input' => $this->nullableInt($usage->promptTokens ?? $usage->inputTokens ?? null),
                'output' => $this->nullableInt($usage->completionTokens ?? $usage->outputTokens ?? null),
            ];
        }

        if (is_array($usage)) {
            return [
                'input' => $this->nullableInt($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? null),
                'output' => $this->nullableInt($usage['completion_tokens'] ?? $usage['output_tokens'] ?? null),
            ];
        }

        return ['input' => null, 'output' => null];
    }

    public function record(
        string $provider,
        string $model,
        string $operation,
        ?int $inputTokens,
        ?int $outputTokens,
        int $durationMs,
        string $status,
    ): void {
        try {
            AiMetric::query()->create([
                'provider' => $provider,
                'model' => $model,
                'operation' => $operation,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'duration_ms' => max(0, $durationMs),
                'status' => $status,
                'recorded_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Observability must never break article processing or leak request data.
            Log::warning('Não foi possível registrar métrica de IA.', [
                'provider' => $provider,
                'model' => $model,
                'operation' => $operation,
                'exception' => $exception::class,
            ]);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }
}
