<?php

use App\Livewire\Student\Survey as StudentSurvey;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Livewire\Livewire;

beforeEach(function () {
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
});

test('student survey page is displayed', function () {
    $this->get(route('student.survey.show'))
        ->assertOk()
        ->assertSee('Studentenenquete');
});

test('first question shows a visible progress bar', function () {
    Livewire::test(StudentSurvey::class)
        ->assertSeeHtml('data-test="survey-progress-bar"')
        ->assertSee('Vraag 1 van 4')
        ->assertSee('25% voltooid')
        ->assertSeeHtml('style="width: 25%;"');
});

test('progress updates when moving to the next question', function () {
    Livewire::test(StudentSurvey::class)
        ->call('nextQuestion')
        ->assertSet('currentQuestionIndex', 1)
        ->assertSee('Vraag 2 van 4')
        ->assertSee('50% voltooid')
        ->assertSeeHtml('style="width: 50%;"');
});

test('progress remains visible and correct when moving to the previous question', function () {
    Livewire::test(StudentSurvey::class)
        ->call('nextQuestion')
        ->call('nextQuestion')
        ->assertSee('Vraag 3 van 4')
        ->assertSee('75% voltooid')
        ->call('previousQuestion')
        ->assertSet('currentQuestionIndex', 1)
        ->assertSeeHtml('data-test="survey-progress-bar"')
        ->assertSee('Vraag 2 van 4')
        ->assertSee('50% voltooid')
        ->assertSeeHtml('style="width: 50%;"');
});

test('last question shows full progress', function () {
    Livewire::test(StudentSurvey::class)
        ->call('nextQuestion')
        ->call('nextQuestion')
        ->call('nextQuestion')
        ->assertSet('currentQuestionIndex', 3)
        ->assertSee('Vraag 4 van 4')
        ->assertSee('100% voltooid')
        ->assertSeeHtml('style="width: 100%;"');
});
