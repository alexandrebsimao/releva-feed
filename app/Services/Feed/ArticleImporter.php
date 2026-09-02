<?php

namespace App\Services\Feed;

use App\Jobs\ProcessArticleJob;
use App\Models\Article;
use App\Models\Feed;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class ArticleImporter
{
    public function __construct(
        private readonly FeedFetcher $fetcher,
        private readonly FeedParser $parser,
    ) {}

    public function import(Feed $feed): ImportReport
    {
        $fetchedAt = now();
        $feed->update(['last_fetched_at' => $fetchedAt]);

        try {
            $items = $this->parser->parse($this->fetcher->fetch($feed), $feed->url);
            $imported = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $data = $this->normalizeArticle($feed, $item);

                if ($data === null || $this->findExisting($data) !== null) {
                    $skipped++;

                    continue;
                }

                try {
                    $article = Article::create($data);
                    ProcessArticleJob::dispatch($article->id);
                    $imported++;
                } catch (UniqueConstraintViolationException) {
                    // Another worker may have imported the same item between lookup and insert.
                    $skipped++;
                }
            }

            $feed->update([
                'last_success_at' => now(),
                'failure_count' => 0,
                'last_error' => null,
            ]);

            return new ImportReport(imported: $imported, skipped: $skipped);
        } catch (\Throwable $exception) {
            $message = Str::limit(trim($exception->getMessage()) ?: $exception::class, 5000, '');

            $feed->update([
                'failure_count' => $feed->failure_count + 1,
                'last_error' => $message,
            ]);

            return new ImportReport(failed: true, error: $message);
        }
    }

    /** @param array<string, mixed> $item */
    private function normalizeArticle(Feed $feed, array $item): ?array
    {
        $url = $item['url'] ?? $item['canonical_url'] ?? null;
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $description = isset($item['description']) ? trim(strip_tags((string) $item['description'])) : null;
        $author = isset($item['author']) ? trim((string) $item['author']) : null;
        $title = trim((string) ($item['original_title'] ?? 'Sem título')) ?: 'Sem título';
        $publishedAt = $item['published_at'] ?? now();
        $contentHash = hash('sha256', implode('|', [$title, $description ?? '', $author ?? '']));

        return [
            'feed_id' => $feed->id,
            'guid' => $this->nullableString($item['guid'] ?? null),
            'url' => $url,
            'canonical_url' => $this->nullableString($item['canonical_url'] ?? null),
            'original_title' => $title,
            'description' => $description !== '' ? $description : null,
            'author' => $author !== '' ? $author : null,
            'image_url' => $this->nullableString($item['image_url'] ?? null),
            'published_at' => $publishedAt,
            'imported_at' => now(),
            'content_hash' => $contentHash,
            'processing_status' => Article::STATUS_PENDING,
        ];
    }

    /** @param array<string, mixed> $data */
    private function findExisting(array $data): ?Article
    {
        if ($data['guid'] !== null && ($article = Article::where('guid', $data['guid'])->first())) {
            return $article;
        }

        if ($data['canonical_url'] !== null) {
            $article = Article::query()
                ->where(fn ($query) => $query
                    ->where('canonical_url', $data['canonical_url'])
                    ->orWhere('url', $data['canonical_url']))
                ->first();

            if ($article !== null) {
                return $article;
            }
        }

        $article = Article::query()
            ->where(fn ($query) => $query
                ->where('url', $data['url'])
                ->orWhere('canonical_url', $data['url']))
            ->first();

        if ($article !== null) {
            return $article;
        }

        return Article::where('content_hash', $data['content_hash'])->first();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }
}
