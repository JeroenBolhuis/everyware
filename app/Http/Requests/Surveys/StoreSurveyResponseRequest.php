<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'student_email' => ['required', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];

        $survey = $this->route('survey');

        if ($survey instanceof Survey) {
            $survey->loadMissing('questions');

            $rules['student_email'][] = Rule::unique('survey_responses', 'student_email')
                ->where('survey_id', $survey->id);

            foreach ($survey->questions as $question) {
                $rules["answers.{$question->id}"] = $question->required ? ['required'] : ['nullable'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'student_email.required' => 'Vul je e-mailadres in om de enquete te versturen.',
            'student_email.email' => 'Vul een geldig e-mailadres in.',
            'student_email.unique' => 'Je hebt deze enquete al ingevuld met dit e-mailadres.',
            'contact_email.email' => 'Vul een geldig e-mailadres in.',
        ];
    }
}
