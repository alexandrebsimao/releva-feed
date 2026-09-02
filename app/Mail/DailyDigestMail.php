<?php

namespace App\Mail;

use App\Models\DailyDigest;
use App\Models\NewsCluster;
use App\Models\UserTopicPreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class DailyDigestMail extends Mailable
{
    public function __construct(public readonly DailyDigest $digest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu resumo diário · Releva Feed');
    }

    public function content(): Content
    {
        $topicsByCluster = [];

        foreach ($this->digest->items as $item) {
            $topicsByCluster[$item->newsCluster->getKey()] = $this->topicsFor($item->newsCluster);
        }

        return new Content(view: 'mail.daily-digest', with: compact('topicsByCluster'));
    }

    public function topicsFor(NewsCluster $cluster): Collection
    {
        $followedIds = $this->digest->user->topicPreferences
            ->where('preference', UserTopicPreference::PREFERENCE_FOLLOW)
            ->pluck('topic_id');

        return $cluster->articles
            ->flatMap(fn ($article) => $article->topics)
            ->whereIn('id', $followedIds)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
}
