<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTopicRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_and_topics_have_a_many_to_many_relationship_with_metadata(): void
    {
        $article = Article::factory()->create();
        $topic = Topic::factory()->create(['status' => Topic::STATUS_APPROVED]);

        $article->topics()->attach($topic, ['confidence' => 0.8750, 'detected_by' => 'manual']);

        $this->assertTrue($article->topics->contains($topic));
        $this->assertTrue($topic->articles->contains($article));
        $this->assertSame('manual', $article->topics->first()->pivot->detected_by);
        $this->assertEquals(0.875, (float) $article->topics->first()->pivot->confidence);
    }
}
