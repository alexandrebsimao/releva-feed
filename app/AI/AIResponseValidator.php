<?php

namespace App\AI;

use App\Models\Topic;
use Illuminate\Support\Facades\Validator;

class AIResponseValidator
{
    /** @param array<string, mixed> $response @return array<string, mixed> */
    public function classification(array $response): array
    {
        return $this->validate($response, [
            'topics' => ['required', 'array'],
            'topics.*.name' => ['required', 'string', 'max:255'],
            'topics.*.type' => ['required', 'string', 'in:'.implode(',', Topic::TYPES)],
            'topics.*.confidence' => ['required', 'numeric', 'between:0,1'],
            'importance' => ['required', 'integer', 'between:0,100'],
            'is_news' => ['required', 'boolean'],
            'language' => ['required', 'string', 'max:20'],
        ]);
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    public function topics(array $response): array
    {
        return $this->validate($response, [
            'topics' => ['required', 'array'],
            'topics.*.name' => ['required', 'string', 'max:255'],
            'topics.*.type' => ['required', 'string', 'in:'.implode(',', Topic::TYPES)],
            'topics.*.confidence' => ['required', 'numeric', 'between:0,1'],
        ]);
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    public function relevance(array $response): array
    {
        return $this->validate($response, [
            'is_relevant' => ['required', 'boolean'],
            'score' => ['required', 'integer', 'between:0,100'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    public function summary(array $response): array
    {
        return $this->validate($response, [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:1200'],
            'why_it_matters' => ['required', 'string', 'max:500'],
            'importance' => ['required', 'integer', 'between:0,100'],
        ]);
    }

    /** @param array<string, mixed> $response @param array<string, array<int, string>> $rules @return array<string, mixed> */
    private function validate(array $response, array $rules): array
    {
        return Validator::make($response, $rules)->validate();
    }
}
