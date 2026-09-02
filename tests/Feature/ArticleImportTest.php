<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Source;
use App\Models\User;
use App\Services\Feed\ArticleImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_rss_articles_and_updates_feed_success_metrics(): void
    {
        Http::fake(['https://news.example.com/feed.xml' => Http::response(file_get_contents(base_path('tests/Fixtures/rss.xml')), 200)]);
        $feed = Feed::factory()->create(['url' => 'https://news.example.com/feed.xml']);

        $report = app(ArticleImporter::class)->import($feed);

        $this->assertFalse($report->failed);
        $this->assertSame(2, $report->imported);
        $this->assertSame(0, $report->skipped);
        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('feeds', ['id' => $feed->id, 'failure_count' => 0, 'last_error' => null]);
        $this->assertNotNull($feed->fresh()->last_fetched_at);
        $this->assertNotNull($feed->fresh()->last_success_at);
    }

    public function test_it_deduplicates_by_guid_then_url_and_content_hash_without_replacing_existing_articles(): void
    {
        Http::fake(['https://news.example.com/feed.xml' => Http::response(file_get_contents(base_path('tests/Fixtures/rss.xml')), 200)]);
        $feed = Feed::factory()->create(['url' => 'https://news.example.com/feed.xml']);
        $firstReport = app(ArticleImporter::class)->import($feed);
        $original = Article::where('guid', 'article-first-001')->firstOrFail();
        $originalTitle = $original->original_title;

        $secondReport = app(ArticleImporter::class)->import($feed->fresh());

        $this->assertSame(2, $firstReport->imported);
        $this->assertSame(0, $secondReport->imported);
        $this->assertSame(2, $secondReport->skipped);
        $this->assertSame($originalTitle, $original->fresh()->original_title);
    }

    public function test_it_deduplicates_same_content_when_guid_and_url_differ(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<rss version="2.0"><channel><item>
    <title>Mesmo acontecimento</title>
    <link>https://one.example.com/story</link>
    <guid>guid-one</guid>
    <description>O mesmo texto.</description>
</item></channel></rss>
XML;
        Http::fakeSequence()->push($xml, 200)->push(str_replace(['one.example.com/story', 'guid-one'], ['two.example.com/story', 'guid-two'], $xml), 200);
        $source = Source::factory()->create();
        $firstFeed = Feed::factory()->create(['source_id' => $source->id, 'url' => 'https://one.example.com/feed.xml']);
        $secondFeed = Feed::factory()->create(['source_id' => $source->id, 'url' => 'https://two.example.com/feed.xml']);

        app(ArticleImporter::class)->import($firstFeed);
        $report = app(ArticleImporter::class)->import($secondFeed);

        $this->assertSame(0, $report->imported);
        $this->assertSame(1, $report->skipped);
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_it_records_fetch_failures_on_the_feed(): void
    {
        Http::fake(['https://news.example.com/feed.xml' => Http::response('server error', 500)]);
        $feed = Feed::factory()->create(['url' => 'https://news.example.com/feed.xml', 'failure_count' => 2]);

        $report = app(ArticleImporter::class)->import($feed);
        $feed = $feed->fresh();

        $this->assertTrue($report->failed);
        $this->assertSame(3, $feed->failure_count);
        $this->assertNotNull($feed->last_fetched_at);
        $this->assertNull($feed->last_success_at);
        $this->assertStringContainsString('500', $feed->last_error);
    }

    public function test_articles_page_requires_authentication_and_lists_imported_articles(): void
    {
        $this->get('/articles')->assertRedirect('/login');

        $user = User::factory()->create();
        $article = Article::factory()->create(['original_title' => 'Título administrativo']);

        $this->actingAs($user)->get('/articles')->assertOk()->assertSee('Título administrativo');
    }
}
