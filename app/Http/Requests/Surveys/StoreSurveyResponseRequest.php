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
}
