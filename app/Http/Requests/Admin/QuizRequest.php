<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in(Quiz::CATEGORIES)],
            'difficulty' => ['required', 'string', Rule::in(Quiz::DIFFICULTIES)],
            'description' => ['nullable', 'string', 'max:500'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'is_published' => ['boolean'],
        ];
    }
}
