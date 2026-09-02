<?php

namespace Tests\Unit;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_feed_dispatch_job_is_scheduled_every_six_hours_without_overlap(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => $event->getExpression() === '0 */6 * * *');

        $this->assertNotNull($event);
        $this->assertSame('dispatch-feed-fetches', $event->description);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_digest_and_topic_discovery_jobs_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());

        $digest = $events->first(fn ($event) => $event->getExpression() === '*/15 * * * *');
        $discovery = $events->first(fn ($event) => $event->getExpression() === '30 3 * * *');

        $this->assertNotNull($digest);
        $this->assertSame('build-daily-digests', $digest->description);
        $this->assertTrue($digest->withoutOverlapping);
        $this->assertNotNull($discovery);
        $this->assertSame('discover-user-topics', $discovery->description);
        $this->assertTrue($discovery->withoutOverlapping);
    }

    public function test_old_articles_are_scheduled_for_daily_cleanup(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => $event->getExpression() === '15 4 * * *');

        $this->assertNotNull($event);
        $this->assertSame('prune-old-articles', $event->description);
        $this->assertTrue($event->withoutOverlapping);
    }
}
