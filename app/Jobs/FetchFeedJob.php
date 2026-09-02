<?php

namespace App\Jobs;

use App\Models\Feed;
use App\Services\Feed\ArticleImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FetchFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $feedId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ArticleImporter $importer): void
    {
        $feed = Feed::query()->find($this->feedId);

        if ($feed === null) {
            Log::notice('Feed removido antes do processamento.', ['feed_id' => $this->feedId]);

            return;
        }

        if (! $feed->active || ! $feed->source()->where('active', true)->exists()) {
            return;
        }

        $report = $importer->import($feed);

        if ($report->failed) {
            Log::warning('Falha ao processar feed; o job será re-tentado.', [
                'feed_id' => $feed->id,
                'error' => $report->error,
            ]);

            throw new RuntimeException($report->error ?: 'Falha desconhecida ao processar feed.');
        }

        Log::info('Feed processado.', [
            'feed_id' => $feed->id,
            'imported' => $report->imported,
            'skipped' => $report->skipped,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Job de feed esgotou as tentativas.', [
            'feed_id' => $this->feedId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
