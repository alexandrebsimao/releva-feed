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
}
