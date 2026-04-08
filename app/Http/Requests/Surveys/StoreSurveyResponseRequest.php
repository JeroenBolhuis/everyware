<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'answers' => ['required', 'array'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];

        $survey = $this->route('survey');

        if ($survey instanceof Survey) {
            $survey->loadMissing('questions');

            foreach ($survey->questions as $question) {
                $rules["answers.{$question->id}"] = $question->required ? ['required'] : ['nullable'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_email.email' => 'Vul een geldig e-mailadres in.',
        ];
    }
}
