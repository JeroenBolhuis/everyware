<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;

test('survey show page renders the progress bar with question counts and percentages', function () {
    $survey = Survey::factory()->active()->create([
        'title' => 'Onderwijsevaluatie',
    ]);

    collect([
        [
            'question' => 'Hoe ervaar je de huidige lessen tot nu toe?',
            'type' => 'textarea',
            'options' => null,
            'required' => true,
            'sort_order' => 1,
        ],
        [
            'question' => 'Hoe ervaar je de studielast van deze periode?',
            'type' => 'textarea',
            'options' => null,
            'required' => true,
            'sort_order' => 2,
        ],
        [
            'question' => 'Voel je je voldoende begeleid door docenten en studiebegeleiding?',
            'type' => 'textarea',
            'options' => null,
            'required' => true,
            'sort_order' => 3,
        ],
        [
            'question' => 'Welke ene verbetering zou voor jou de grootste impact hebben?',
            'type' => 'textarea',
            'options' => null,
            'required' => true,
            'sort_order' => 4,
        ],
    ])->each(fn (array $question) => SurveyQuestion::factory()->for($survey)->create($question));

    $this->get(route('survey.show', $survey))
        ->assertOk()
        ->assertSeeHtml('data-test="survey-progress-bar"')
        ->assertSee('Vraag 1 van 4')
        ->assertSee('Vraag 2 van 4')
        ->assertSee('Vraag 3 van 4')
        ->assertSee('Vraag 4 van 4')
        ->assertSee('25% voltooid')
        ->assertSee('50% voltooid')
        ->assertSee('75% voltooid')
        ->assertSee('100% voltooid')
        ->assertSeeHtml('style="width: 25%"')
        ->assertSeeHtml('style="width: 50%"')
        ->assertSeeHtml('style="width: 75%"')
        ->assertSeeHtml('style="width: 100%"');
});
