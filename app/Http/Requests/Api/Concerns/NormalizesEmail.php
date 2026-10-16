<?php

namespace App\Http\Requests\Api\Concerns;

trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        if ($this->exists('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
