<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts question options to an array and required to a boolean', function () {
    $survey = Survey::create([
        'title' => 'Vraagtypes',
        'description' => 'Beschrijving',
        'is_active' => true,
    ]);

    $question = SurveyQuestion::create([
        'survey_id' => $survey->id,
        'question' => 'Kies een antwoord',
        'type' => 'radio',
        'options' => ['Ja', 'Nee'],
        'required' => 1,
        'sort_order' => 1,
    ]);

    expect($question->options)->toBe(['Ja', 'Nee']);
    expect($question->required)->toBeTrue();
    expect($question->survey->is($survey))->toBeTrue();
});
