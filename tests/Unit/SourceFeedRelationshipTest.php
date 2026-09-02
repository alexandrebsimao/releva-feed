<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceFeedRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_has_many_feeds_and_feed_belongs_to_source(): void
    {
        $source = Source::factory()->create();
        $feed = Feed::factory()->create(['source_id' => $source->id]);
        $article = Article::factory()->create(['feed_id' => $feed->id]);

        $this->assertTrue($source->feeds->contains($feed));
        $this->assertTrue($feed->source->is($source));
        $this->assertTrue($feed->articles->contains($article));
    }
}
