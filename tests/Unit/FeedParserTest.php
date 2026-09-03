<?php

namespace Tests\Unit;

use App\Services\Feed\FeedParser;
use Tests\TestCase;

class FeedParserTest extends TestCase
{
    public function test_it_parses_rss_2_items_and_normalizes_their_fields(): void
    {
        $items = app(FeedParser::class)->parse(file_get_contents(base_path('tests/Fixtures/rss.xml')), 'https://news.example.com/feed.xml');

        $this->assertCount(2, $items);
        $this->assertSame('article-first-001', $items[0]['guid']);
        $this->assertSame('https://news.example.com/articles/first', $items[0]['url']);
        $this->assertSame('Ana Jornalista', $items[0]['author']);
        $this->assertSame('https://news.example.com/images/first.jpg', $items[0]['image_url']);
        $this->assertSame('Primeira notícia do dia', $items[0]['original_title']);
        $this->assertNotNull($items[0]['published_at']);
    }

    public function test_it_parses_atom_entries_and_canonical_links(): void
    {
        $items = app(FeedParser::class)->parse(file_get_contents(base_path('tests/Fixtures/atom.xml')), 'https://atom.example.com/feed.xml');

        $this->assertCount(1, $items);
        $this->assertSame('tag:atom.example.com,2026:entry-1', $items[0]['guid']);
        $this->assertSame('https://atom.example.com/entries/1', $items[0]['url']);
        $this->assertSame('https://atom.example.com/entries/1?view=canonical', $items[0]['canonical_url']);
        $this->assertSame('Bruno Editor', $items[0]['author']);
    }

    public function test_it_converts_feed_dates_to_the_application_timezone(): void
    {
        $items = app(FeedParser::class)->parse(<<<'XML'
            <rss version="2.0">
                <channel>
                    <item>
                        <guid>timezone-001</guid>
                        <title>Notícia com fuso</title>
                        <link>https://news.example.com/timezone</link>
                        <pubDate>Tue, 08 Sep 2026 12:00:00 GMT</pubDate>
                    </item>
                </channel>
            </rss>
            XML, 'https://news.example.com/feed.xml');

        $this->assertSame('2026-09-08 09:00:00', $items[0]['published_at']->format('Y-m-d H:i:s'));
        $this->assertSame('America/Sao_Paulo', $items[0]['published_at']->getTimezone()->getName());
    }
}
