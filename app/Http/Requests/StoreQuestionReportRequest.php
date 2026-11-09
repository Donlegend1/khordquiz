<?php

namespace App\Http\Requests;

use App\Models\QuestionReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer'],
            'session_id' => ['nullable', 'integer'],
            'reason' => ['required', Rule::in(QuestionReport::REASONS)],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
