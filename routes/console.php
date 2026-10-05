<?php

use App\Jobs\BuildDailyDigestsJob;
use App\Jobs\DiscoverTopicsJob;
use App\Jobs\DispatchFeedFetchesJob;
use Illuminate\Support\Facades\Schedule;

$feedSchedule = Schedule::job(new DispatchFeedFetchesJob)
    ->name('dispatch-feed-fetches')
    ->withoutOverlapping();

$feedFrequency = env('FEEDS_SCHEDULE_FREQUENCY', 'everySixHours');

match ($feedFrequency) {
    'everyFifteenMinutes' => $feedSchedule->everyFifteenMinutes(),
    'everyThirtyMinutes' => $feedSchedule->everyThirtyMinutes(),
    'hourly' => $feedSchedule->hourly(),
    'everyTwoHours' => $feedSchedule->everyTwoHours(),
    'everyThreeHours' => $feedSchedule->everyThreeHours(),
    default => $feedSchedule->everySixHours(),
};

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
