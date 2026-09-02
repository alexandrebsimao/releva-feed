<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

class AiRequestThrottle
{
    public function wait(): void
    {
        $intervalMs = max(0, (int) config('ai.request_interval_ms', 3000));

        if ($intervalMs === 0) {
            return;
        }

        $provider = trim((string) config('ai.provider', 'groq')) ?: 'unknown';
        $lock = Cache::lock('ai:request-throttle:'.$provider, max(10, (int) ceil($intervalMs / 1000) + 5));

        $lock->block(15, function () use ($intervalMs, $provider): void {
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
