<?php

namespace App\Jobs;

use App\Mail\DailyDigestMail;
use App\Models\DailyDigest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyDigestJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 900;

    public function __construct(public readonly int $digestId)
    {
        $this->onQueue('digests');
    }

    public function uniqueId(): string
    {
        return (string) $this->digestId;
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $digest = DailyDigest::query()->with([
            'user.topicPreferences',
            'items.newsCluster.articles.feed.source',
            'items.newsCluster.articles.topics',
        ])->find($this->digestId);

        if ($digest === null || $digest->items->isEmpty()) {
            return;
        }

        $claimed = DailyDigest::query()
            ->whereKey($digest->getKey())
            ->whereIn('status', [DailyDigest::STATUS_READY, DailyDigest::STATUS_FAILED])
            ->update(['status' => DailyDigest::STATUS_SENDING]);

        if ($claimed !== 1) {
            return;
        }

        try {
            Mail::to($digest->user->email)->send(new DailyDigestMail($digest));
            $digest->update([
                'status' => DailyDigest::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $digest->update(['status' => DailyDigest::STATUS_FAILED]);
            Log::error('Falha ao enviar digest diário; o job será re-tentado.', [
                'digest_id' => $digest->id,
                'user_id' => $digest->user_id,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
