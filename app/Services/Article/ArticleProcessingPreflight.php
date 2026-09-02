<?php

namespace App\Services\Article;

use App\Models\Article;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ArticleProcessingPreflight
{
    public function reason(Article $article): ?string
    {
        if (in_array($article->processing_status, [Article::STATUS_PROCESSED, Article::STATUS_IGNORED], true)) {
            return 'already_processed';
        }

        if ($article->processing_status === Article::STATUS_PROCESSING) {
            return 'already_processing';
        }

        try {
            $publishedAt = $article->published_at;
        } catch (\Throwable) {
            return 'invalid_date';
        }

        if (! $publishedAt instanceof CarbonInterface || ! $publishedAt->isValid()) {
            return 'invalid_date';
        }

        if ($this->content($article) === '') {
            return 'insufficient_content';
        }

        if ($this->isClearlyIncompatibleLanguage($article)) {
            return 'incompatible_language';
        }

        if ($this->isDuplicate($article)) {
            return 'duplicate';
        }

        return null;
    }

    private function content(Article $article): string
    {
        $content = trim(preg_replace('/\s+/u', ' ', strip_tags($article->original_title.' '.$article->description)) ?? '');

        return Str::length($content) >= 40 ? $content : '';
    }

    private function isClearlyIncompatibleLanguage(Article $article): bool
    {
        $content = Str::lower($this->content($article));

        if ($content === '') {
            return false;
        }

        $portugueseMarkers = preg_match_all('/\b(os|as|um|uma|de|da|do|em|no|na|que|para|com|não|notícia|sobre|e|é)\b/u', $content) ?: 0;
        $englishMarkers = preg_match_all('/\b(the|an|of|and|for|with|that|this|news|about|from|has|are|is)\b/u', $content) ?: 0;

        return $englishMarkers >= 3 && $portugueseMarkers === 0;
    }

    private function isDuplicate(Article $article): bool
    {
        return Article::query()
            ->where('id', '!=', $article->getKey())
            ->where(function ($query) use ($article): void {
                $query->where('content_hash', $article->content_hash);

                if ($article->guid !== null) {
                    $query->orWhere('guid', $article->guid);
                }

                foreach (array_filter([$article->url, $article->canonical_url]) as $url) {
                    $query->orWhere('url', $url)->orWhere('canonical_url', $url);
                }
            })
            ->exists();
    }
}
