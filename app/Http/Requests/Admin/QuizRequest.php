<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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
        $questions = $this->input('questions', []);

        if (is_array($questions)) {
            foreach ($questions as $index => $question) {
                if (! is_array($question)) {
                    continue;
                }

                $questions[$index]['prompt'] = is_string($question['prompt'] ?? null)
                    ? trim($question['prompt'])
                    : ($question['prompt'] ?? '');

                if (isset($question['choices']) && is_array($question['choices'])) {
                    $questions[$index]['choices'] = array_map(
                        fn ($choice) => is_string($choice) ? trim($choice) : $choice,
                        $question['choices'],
                    );
                }

                $id = $question['id'] ?? null;
                $questions[$index]['id'] = $id === '' || $id === null ? null : $id;
                $questions[$index]['keep_audio'] = filter_var($question['keep_audio'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $questions[$index]['keep_video'] = filter_var($question['keep_video'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
            'questions' => $questions,
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
            'is_published' => ['boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:30'],
            'questions.*.prompt' => ['required', 'string', 'max:300'],
            'questions.*.choices' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.choices.*' => ['required', 'string', 'max:120'],
            'questions.*.correct' => ['required', 'integer', 'min:0'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.keep_audio' => ['boolean'],
            'questions.*.keep_video' => ['boolean'],
            'questions.*.audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm', 'max:15360'],
            'questions.*.video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('questions', []) as $index => $question) {
                if (! is_array($question) || ! is_array($question['choices'] ?? null)) {
                    continue;
                }

                $correct = $question['correct'] ?? null;

                if (! is_numeric($correct) || ! array_key_exists((int) $correct, $question['choices'])) {
                    $validator->errors()->add("questions.{$index}.correct", 'Choose which answer is correct.');
                }

                $choices = array_values(array_filter(
                    $question['choices'],
                    fn ($choice) => is_string($choice) && $choice !== '',
                ));

                if (count($choices) !== count(array_unique($choices))) {
                    $validator->errors()->add("questions.{$index}.choices", 'Answers in a question must be different.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'questions.required' => 'Add at least one question.',
            'questions.min' => 'Add at least one question.',
            'questions.*.prompt.required' => 'Write the question.',
            'questions.*.choices.required' => 'Add answers for this question.',
            'questions.*.choices.min' => 'Add at least two answers.',
            'questions.*.choices.*.required' => 'Write each answer.',
            'questions.*.choices' => 'Answers in a question must be different.',
            'questions.*.correct.required' => 'Choose which answer is correct.',
            'questions.*.audio.mimes' => 'Audio must be an mp3, wav, m4a, aac, ogg, or webm file.',
            'questions.*.audio.max' => 'Audio must be 15 MB or smaller.',
            'questions.*.video.mimes' => 'Video must be an mp4, webm, or mov file.',
            'questions.*.video.max' => 'Video must be 50 MB or smaller.',
        ];
    }
}
