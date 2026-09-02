<?php

namespace App\Http\Requests;

use App\Models\Topic;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends StoreTopicRequest
{
    public function rules(): array
    {
        $topic = $this->route('topic');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', Rule::unique('topics', 'slug')->ignore($topic)],
            'type' => ['required', Rule::in(Topic::TYPES)],
            'status' => ['required', Rule::in(Topic::STATUSES)],
            'active' => ['required', 'boolean'],
            'aliases' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
