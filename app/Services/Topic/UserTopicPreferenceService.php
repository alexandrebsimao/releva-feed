<?php

namespace App\Services\Topic;

use App\Models\Article;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicPreference;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class UserTopicPreferenceService
{
    public function setPreference(
        User $user,
        Topic $topic,
        string $preference,
        int $priority = UserTopicPreference::PRIORITY_NORMAL,
    ): UserTopicPreference {
        $this->validatePreference($preference);
        $this->validatePriority($priority);

        return UserTopicPreference::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'topic_id' => $topic->getKey()],
            ['preference' => $preference, 'priority' => $priority],
        );
    }

    public function updatePriority(User $user, Topic $topic, int $priority): UserTopicPreference
    {
        $this->validatePriority($priority);

        $preference = UserTopicPreference::query()
            ->where('user_id', $user->getKey())
            ->where('topic_id', $topic->getKey())
            ->firstOrFail();

        $preference->update(['priority' => $priority]);

        return $preference->refresh();
    }

    public function removePreference(User $user, Topic $topic): void
    {
        UserTopicPreference::query()
            ->where('user_id', $user->getKey())
            ->where('topic_id', $topic->getKey())
            ->delete();
    }

    public function radarQuery(User $user): Builder
    {
        $followedTopicIds = $this->topicIdsFor($user, UserTopicPreference::PREFERENCE_FOLLOW);
        $excludedTopicIds = $this->topicIdsFor($user, UserTopicPreference::PREFERENCE_EXCLUDE);

        return Article::query()
            ->whereHas('topics', fn (Builder $query) => $query->whereIn('topics.id', $followedTopicIds))
            ->whereDoesntHave('topics', fn (Builder $query) => $query->whereIn('topics.id', $excludedTopicIds));
    }

    public function shouldIncludeArticle(User $user, Article $article): bool
    {
        $topicIds = $article->relationLoaded('topics')
            ? $article->topics->modelKeys()
            : $article->topics()->pluck('topics.id')->all();

        if ($topicIds === []) {
            return false;
        }

        $preferences = UserTopicPreference::query()
            ->where('user_id', $user->getKey())
            ->whereIn('topic_id', $topicIds)
            ->pluck('preference');

        // An exclusion always wins when an article matches both kinds of preference.
        if ($preferences->contains(UserTopicPreference::PREFERENCE_EXCLUDE)) {
            return false;
        }

        return $preferences->contains(UserTopicPreference::PREFERENCE_FOLLOW);
    }

    /** @return Builder<UserTopicPreference> */
    private function topicIdsFor(User $user, string $preference): Builder
    {
        return UserTopicPreference::query()
            ->select('topic_id')
            ->where('user_id', $user->getKey())
            ->where('preference', $preference);
    }

    private function validatePreference(string $preference): void
    {
        if (! in_array($preference, UserTopicPreference::PREFERENCES, true)) {
            throw new InvalidArgumentException('Preferência de assunto inválida.');
        }
    }

    private function validatePriority(int $priority): void
    {
        if (! in_array($priority, UserTopicPreference::PRIORITIES, true)) {
            throw new InvalidArgumentException('Prioridade de assunto inválida.');
        }
    }
}
