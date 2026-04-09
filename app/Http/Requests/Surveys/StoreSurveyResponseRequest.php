<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('contact_email')) {
            $this->merge([
                'contact_email' => Str::lower(trim($this->input('contact_email'))),
            ]);
        }
    }

    public function rules(): array
    {
        $survey = $this->route('survey');

        $rules = [
            'answers'       => ['required', 'array'],
            'contact_name'  => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
        ];

        if ($survey instanceof Survey) {
            $survey->loadMissing('questions');

            foreach ($survey->questions as $question) {
                $rules["answers.{$question->id}"] = $question->required ? ['required'] : ['nullable'];
            }

            // Prevent duplicate submissions: one response per email per survey
            $rules['contact_email'][] = Rule::unique('survey_responses', 'student_email')
                ->where('survey_id', $survey->id)
                ->whereNull('withdrawn_at');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_email.required' => 'Vul je e-mailadres in.',
            'contact_email.email'    => 'Vul een geldig e-mailadres in.',
            'contact_email.unique'   => 'Dit e-mailadres heeft de enquête al ingevuld.',
        ];
    }
}
