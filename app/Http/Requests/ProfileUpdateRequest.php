<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Email isn't here — it's locked on the Edit Profile form and the
     * controller discards any submitted value, so there's nothing to validate.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'skill_level' => ['nullable', 'string', Rule::in(User::SKILL_LEVELS)],
        ];
    }
}
