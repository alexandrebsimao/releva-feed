<?php

namespace App\Jobs;

use App\Models\DailyDigest;
use App\Models\User;
use App\Services\News\DigestBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildDailyDigestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('digests');
    }

    public function handle(DigestBuilder $builder): void
    {
        User::query()
            ->where('digest_enabled', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($builder): void {
                foreach ($users as $user) {
                    if (! $builder->isDue($user)) {
                        continue;
                    }

                    $digest = $builder->build($user);

                    if (in_array($digest->status, [DailyDigest::STATUS_READY, DailyDigest::STATUS_FAILED], true)) {
                        SendDailyDigestJob::dispatch($digest->id);
                    }
                }
            });
    }
}
