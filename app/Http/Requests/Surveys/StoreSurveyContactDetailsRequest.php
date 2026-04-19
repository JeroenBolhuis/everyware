<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyContactDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
