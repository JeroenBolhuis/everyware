<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSurveyContactDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user('participant') && ($this->filled('contact_name') || $this->filled('contact_phone'))) {
            $this->merge([
                'contact_email' => $this->user('participant')->email,
            ]);

            return;
        }

        if ($this->filled('contact_email')) {
            $this->merge([
                'contact_email' => Str::lower(trim((string) $this->input('contact_email'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_email.email' => 'Vul een geldig e-mailadres in.',
            'contact_phone.regex' => 'Vul een geldig telefoonnummer in.',
        ];
    }
}
