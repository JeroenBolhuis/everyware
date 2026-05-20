<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $survey = $this->surveyFromRoute();

        return ! $survey?->hasEnded();
    }

    public function rules(): array
    {
        $survey = $this->surveyFromRoute();

        $rules = [
            'answers' => ['required', 'array'],
        ];

        if ($survey instanceof Survey) {
            $survey->loadMissing('questions');

            foreach ($survey->questions as $question) {
                $rules["answers.{$question->id}"] = $question->required ? ['required'] : ['nullable'];
            }
        }

        return $rules;
    }

    protected function failedAuthorization(): void
    {
        $survey = $this->surveyFromRoute();

        if ($survey?->hasEnded()) {
            throw new HttpResponseException(
                response()->view('surveys.expired', compact('survey'), 410)
            );
        }

        parent::failedAuthorization();
    }

    private function surveyFromRoute(): ?Survey
    {
        $survey = $this->route('survey');

        if ($survey instanceof Survey) {
            return $survey;
        }

        $token = $this->route('token');

        if (is_string($token)) {
            return Survey::query()->where('share_token', $token)->first();
        }

        return null;
    }
}
