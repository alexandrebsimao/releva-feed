<?php

namespace App\AI;

use App\Models\Article;
use App\Models\Topic;
use App\Models\TopicAlias;
use App\Services\Topic\TopicNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopicCatalog
{
    public function __construct(private readonly TopicNormalizer $normalizer) {}

    /** @param array<string, mixed> $topicData */
    public function resolveOrCreate(array $topicData): Topic
    {
        $name = trim((string) $topicData['name']);
        $normalizedName = $this->normalizer->normalize($name);

        $existing = TopicAlias::query()
            ->where('normalized_name', $normalizedName)
            ->with('topic')
            ->first()?->topic;

        if ($existing !== null) {
            return $existing;
        }

        $existing = Topic::query()
            ->where('slug', Str::slug($name))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $existing = Topic::query()->get()->first(
            fn (Topic $topic): bool => $this->normalizer->normalize($topic->name) === $normalizedName,
        );

        if ($existing !== null) {
            return $existing;
        }

        return Topic::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'type' => $topicData['type'],
            'status' => Topic::STATUS_APPROVED,
            'active' => true,
        ]);
    }

    /** @param list<array<string, mixed>> $topics */
    public function attachToArticle(Article $article, array $topics): void
    {
        $article->loadMissing('feed');
        $feedCategory = $this->editorialCategoryForFeed($article->feed?->category);

        if ($feedCategory !== null && ! collect($topics)->contains(fn (array $topic): bool => $this->sameName($topic['name'], $feedCategory['name']))) {
            array_unshift($topics, [
                'name' => $feedCategory['name'],
                'type' => Topic::TYPE_TOPIC,
                'confidence' => 1.0,
                'detected_by' => 'feed_category',
            ]);
        }

        DB::transaction(function () use ($article, $topics): void {
            foreach ($topics as $topicData) {
                $topic = $this->resolveOrCreate($topicData);
                $article->topics()->syncWithoutDetaching([
                    $topic->getKey() => [
                        'confidence' => $topicData['confidence'],
                        'detected_by' => $topicData['detected_by'] ?? 'ai',
                    ],
                ]);
            }
        });
    }

    /** @return array{name: string, slug: string}|null */
    private function editorialCategoryForFeed(?string $feedCategory): ?array
    {
        $normalized = $this->normalizer->normalize((string) $feedCategory);

        if ($normalized === '') {
            return null;
        }

        foreach (config('topics.editorial_categories', []) as $category) {
            $feedCategories = array_map(fn (string $value): string => $this->normalizer->normalize($value), $category['feed_categories']);

            if (in_array($normalized, $feedCategories, true)) {
                return $category;
            }
        }

        return null;
    }

    private function sameName(string $first, string $second): bool
    {
        return $this->normalizer->normalize($first) === $this->normalizer->normalize($second);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'topic';
        }

        if (! Topic::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.substr(sha1($name), 0, 8);
    }
}
