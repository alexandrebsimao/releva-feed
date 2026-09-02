<?php

namespace Tests\Feature;

use App\Jobs\DispatchFeedFetchesJob;
use App\Jobs\FetchFeedJob;
use App\Models\Feed;
use App\Models\Source;
use App\Services\Feed\ArticleImporter;
use App\Services\Feed\ImportReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FeedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_job_enqueues_one_job_per_active_feed_from_active_sources(): void
    {
        Queue::fake();
        $activeSource = Source::factory()->create(['active' => true]);
        $inactiveSource = Source::factory()->create(['active' => false]);
        $activeFeed = Feed::factory()->create(['source_id' => $activeSource->id, 'active' => true]);
        Feed::factory()->create(['source_id' => $activeSource->id, 'active' => false]);
        Feed::factory()->create(['source_id' => $inactiveSource->id, 'active' => true]);

        (new DispatchFeedFetchesJob)->handle();

        Queue::assertPushed(FetchFeedJob::class, 1);
        Queue::assertPushed(FetchFeedJob::class, fn (FetchFeedJob $job) => $job->feedId === $activeFeed->id);
    }

    public function test_fetch_job_processes_only_its_feed(): void
    {
        Http::fake(['https://news.example.com/feed.xml' => Http::response(file_get_contents(base_path('tests/Fixtures/rss.xml')), 200)]);
        $feed = Feed::factory()->create(['url' => 'https://news.example.com/feed.xml']);

        (new FetchFeedJob($feed->id))->handle(app(ArticleImporter::class));

        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', ['feed_id' => $feed->id]);
    }

    public function test_fetch_job_rethrows_a_failed_import_so_queue_retry_is_used(): void
    {
        $feed = Feed::factory()->create();
        $importer = Mockery::mock(ArticleImporter::class);
        $importer->shouldReceive('import')->once()->withArgs(fn (Feed $value) => $value->is($feed))->andReturn(new ImportReport(failed: true, error: 'Feed indisponível'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Feed indisponível');

        (new FetchFeedJob($feed->id))->handle($importer);
    }
}
