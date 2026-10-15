<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportMessageRequest extends FormRequest
{
    // Same list the member picks from in the Topic dropdown.
    public const TOPICS = ['Account', 'Billing', 'Quiz issue', 'Bug report', 'Other'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', Rule::in(self::TOPICS)],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'include_device' => ['boolean'],
            'device_info' => ['nullable', 'string', 'max:255'],
            // 10MB — a photo or screen recording frame comfortably fits; kept
            // to common safe types rather than any file type.
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.min' => 'Please tell us a little more — at least 10 characters.',
            'attachment.max' => 'Attachments can be up to 10MB.',
            'attachment.mimes' => 'Attachments must be an image (JPG, PNG, WEBP, GIF) or a PDF.',
        ];
    }
}
