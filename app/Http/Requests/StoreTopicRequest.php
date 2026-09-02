<?php

namespace App\Http\Requests;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', Topic::TYPE_TOPIC),
            'status' => $this->input('status', Topic::STATUS_APPROVED),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:topics,slug'],
            'type' => ['required', Rule::in(Topic::TYPES)],
            'status' => ['required', Rule::in(Topic::STATUSES)],
            'active' => ['required', 'boolean'],
            'aliases' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
