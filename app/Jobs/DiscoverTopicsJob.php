<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Topic\UserTopicDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiscoverTopicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('topics');
    }

    public function handle(UserTopicDiscoveryService $discovery): void
    {
        User::query()
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($discovery): void {
                foreach ($users as $user) {
                    $discovery->discover($user);
                }
            });
    }
}
