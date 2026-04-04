<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageSurveys();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.question' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(['radio', 'swipe', 'textarea'])],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.options' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('questions', []) as $index => $question) {
                $type = $question['type'] ?? null;
                $options = collect(explode(',', (string) ($question['options'] ?? '')))
                    ->map(fn (string $option) => trim($option))
                    ->filter()
                    ->values();

                if (in_array($type, ['radio', 'swipe'], true) && $options->count() < 2) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        'Een radio- of swipe-vraag moet minimaal 2 opties hebben.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Geef de enquête een titel.',
            'questions.required' => 'Voeg minimaal 1 vraag toe aan de enquête.',
            'questions.min' => 'Voeg minimaal 1 vraag toe aan de enquête.',
            'questions.*.question.required' => 'Vul de vraagtekst in.',
            'questions.*.type.required' => 'Kies een vraagtype.',
        ];
    }
}