<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $data = ['active' => $this->boolean('active')];

        if ($this->filled('new_source_name')) {
            $data['source_id'] = null;
        } elseif (! $this->exists('source_id') && $this->route('source') !== null) {
            $routeSource = $this->route('source');
            $data['source_id'] = $routeSource instanceof Source ? $routeSource->getKey() : $routeSource;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'source_id' => ['nullable', 'integer', 'exists:sources,id', 'required_without:new_source_name'],
            'new_source_name' => ['nullable', 'string', 'max:255', 'required_without:source_id'],
            'new_source_domain' => ['nullable', 'string', 'max:255', 'required_with:new_source_name'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'category' => ['nullable', 'string', 'max:100'],
            'active' => ['required', 'boolean'],
        ];
    }
}
