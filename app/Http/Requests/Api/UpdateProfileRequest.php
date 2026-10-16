<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use App\Support\Countries;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', Rule::in(Countries::names())],
            'skill_level' => ['sometimes', 'nullable', 'string', Rule::in(User::SKILL_LEVELS)],
            'avatar' => ['sometimes', 'nullable', 'string', Rule::in(User::AVATARS)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach (['first_name', 'last_name', 'country', 'skill_level', 'avatar'] as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->input($field));

            if (in_array($field, ['country', 'skill_level', 'avatar'], true) && $value === '') {
                $value = null;
            }

            $trimmed[$field] = $value;
        }

        if ($trimmed !== []) {
            $this->merge($trimmed);
        }
    }
}
