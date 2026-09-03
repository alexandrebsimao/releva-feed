<?php

namespace App\Jobs;

use App\AI\NewsSummarizer;
use App\Models\NewsCluster;
use App\Services\News\GeneralScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SummarizeClusterJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 180;

    public int $uniqueFor = 1800;

    public function __construct(public readonly int $clusterId)
    {
        $this->onQueue('articles');
    }

    public function uniqueId(): string
    {
        return (string) $this->clusterId;
    }

    public function backoff(): array
    {
        return [120, 600, 1800];
    }

    public function handle(NewsSummarizer $summarizer, GeneralScoreCalculator $generalScore): void
    {
        $cluster = NewsCluster::query()->find($this->clusterId);

        if ($cluster === null) {
            Log::notice('Cluster removido antes da sumarização.', ['cluster_id' => $this->clusterId]);

            return;
        }

        try {
            $summarizer->summarizeCluster($cluster);
            $generalScore->calculateAndPersist($cluster->refresh());
        } catch (Throwable $exception) {
            $cluster->newQuery()->whereKey($cluster->getKey())->update([
                'ai_last_error' => Str::limit(trim($exception->getMessage()) ?: $exception::class, 2000, '...'),
            ]);

            Log::warning('Falha na sumarização do cluster; o job será re-tentado.', [
                'cluster_id' => $cluster->id,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Job de sumarização esgotou as tentativas.', [
            'cluster_id' => $this->clusterId,
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
