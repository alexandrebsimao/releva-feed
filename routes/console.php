<?php

use App\Jobs\BuildDailyDigestsJob;
use App\Jobs\DiscoverTopicsJob;
use App\Jobs\DispatchFeedFetchesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new DispatchFeedFetchesJob)
    ->name('dispatch-feed-fetches')
    ->everySixHours()
    ->withoutOverlapping();

Schedule::job(new BuildDailyDigestsJob)
    ->name('build-daily-digests')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::job(new DiscoverTopicsJob)
    ->name('discover-user-topics')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('articles:prune')
    ->name('prune-old-articles')
    ->dailyAt('04:15')
    ->withoutOverlapping();
