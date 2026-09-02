<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

class AiRequestThrottle
{
    public function wait(): void
    {
        $intervalMs = max(0, (int) config('ai.request_interval_ms', 5000));

        if ($intervalMs === 0) {
            return;
        }

        $provider = trim((string) config('ai.provider', 'groq')) ?: 'unknown';
        // The lock must outlive the waiting period so parallel queue workers
        // cannot create a burst against the provider's rate limit.
        $lock = Cache::lock('ai:request-throttle:'.$provider, max(60, (int) ceil($intervalMs / 1000) + 30));

        $lock->block(60, function () use ($intervalMs, $provider): void {
            $lastRequestAt = (float) Cache::get('ai:last-request-at:'.$provider, 0);
            $elapsedMs = $lastRequestAt > 0 ? (microtime(true) - $lastRequestAt) * 1000 : $intervalMs;
            $remainingMs = $intervalMs - $elapsedMs;

            if ($remainingMs > 0) {
                usleep((int) ceil($remainingMs * 1000));
            }

            Cache::put('ai:last-request-at:'.$provider, microtime(true), now()->addMinutes(2));
        });
    }
}
