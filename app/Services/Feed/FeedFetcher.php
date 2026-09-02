<?php

namespace App\Services\Feed;

use App\Models\Feed;
use Illuminate\Support\Facades\Http;

class FeedFetcher
{
    public function fetch(Feed $feed): string
    {
        return Http::accept('application/rss+xml, application/atom+xml, application/xml, text/xml')
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(2, 250, throw: false)
            ->get($feed->url)
            ->throw()
            ->body();
    }
}
