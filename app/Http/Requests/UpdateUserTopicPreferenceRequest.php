<?php

namespace App\Http\Requests;

use App\Models\UserTopicPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserTopicPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'integer', Rule::in(UserTopicPreference::PRIORITIES)],
        ];
    }
}
