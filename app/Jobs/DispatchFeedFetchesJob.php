<?php

namespace App\Jobs;

use App\Models\Feed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchFeedFetchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('feeds');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        Feed::query()
            ->where('active', true)
            ->whereHas('source', fn ($query) => $query->where('active', true))
            ->orderBy('id')
            ->chunkById(100, function ($feeds): void {
                foreach ($feeds as $feed) {
                    FetchFeedJob::dispatch($feed->id)->onQueue('feeds');
                }
            });
    }
}
