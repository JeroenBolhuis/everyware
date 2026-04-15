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

            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable'],
            'questions.*.options.*.label' => ['nullable', 'string', 'max:255'],
            'questions.*.options.*.existing_image' => ['nullable', 'string', 'max:2048'],
            'questions.*.options.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('questions', []) as $index => $question) {
                $type = $question['type'] ?? null;
                $rawOptions = $question['options'] ?? [];

                $filledOptions = collect($rawOptions)
                    ->map(function ($option) {
                        if (is_array($option)) {
                            return trim((string) ($option['label'] ?? ''));
                        }

                        return trim((string) $option);
                    })
                    ->filter()
                    ->values();

                if ($type === 'radio' && $filledOptions->count() < 2) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        'Een radio-vraag moet minimaal 2 opties hebben.'
                    );
                }

                if ($type === 'swipe' && $filledOptions->count() !== 2) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        'Een swipe-vraag moet precies 2 opties hebben.'
                    );
                }
            }

            $totalUploadSize = 0;

            foreach ($this->allFiles()['questions'] ?? [] as $questionFiles) {
                foreach (($questionFiles['options'] ?? []) as $optionFiles) {
                    if (!empty($optionFiles['image'])) {
                        $totalUploadSize += $optionFiles['image']->getSize();
                    }
                }
            }

            $maxTotalSize = 5 * 1024 * 1024;

            if ($totalUploadSize > $maxTotalSize) {
                $validator->errors()->add(
                    'questions',
                    'De totale grootte van de geüploade afbeeldingen mag maximaal 5 MB zijn.'
                );
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
            'questions.*.options.*.image.image' => 'Upload een geldig afbeeldingsbestand.',
            'questions.*.options.*.image.mimes' => 'Gebruik een JPG, JPEG, PNG of WEBP afbeelding.',
            'questions.*.options.*.image.max' => 'Een afbeelding mag maximaal 2 MB groot zijn.',
        ];
    }
}