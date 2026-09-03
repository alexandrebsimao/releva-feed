<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $source = $this->route('source');
        $active = auth()->user()?->is_admin === true
            ? $this->boolean('active')
            : ($source instanceof \App\Models\Source ? $source->active : true);

        $this->merge(['active' => $active]);
    }

    public function rules(): array
    {
        $source = $this->route('source');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', Rule::unique('sources', 'slug')->ignore($source)],
            'domain' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'active' => ['required', 'boolean'],
        ];
    }
}
