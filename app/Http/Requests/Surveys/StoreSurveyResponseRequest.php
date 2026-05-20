<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class StoreSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $survey = $this->surveyFromRoute();

        return ! $survey?->hasEnded();
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
        $survey = $this->surveyFromRoute();

        $rules = [
            'answers' => ['required', 'array'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ];

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
