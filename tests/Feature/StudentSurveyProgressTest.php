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
            'question_id' => 'ervaring',
            'prompt' => 'Hoe ervaar je de huidige lessen tot nu toe?',
            'description' => 'Beschrijf kort wat goed gaat en wat volgens jou beter kan.',
            'placeholder' => 'Bijvoorbeeld: de uitleg is duidelijk, maar het tempo ligt hoog...',
            'order' => 1,
        ],
        [
            'question_id' => 'belasting',
            'prompt' => 'Hoe ervaar je de studielast van deze periode?',
            'description' => 'Denk aan opdrachten, lessen en voorbereiding buiten de contacturen.',
            'placeholder' => 'Bijvoorbeeld: goed te doen, soms piekdruk, of juist te licht...',
            'order' => 2,
        ],
        [
            'question_id' => 'begeleiding',
            'prompt' => 'Voel je je voldoende begeleid door docenten en studiebegeleiding?',
            'description' => 'Geef aan wat voor jou helpt of nog ontbreekt.',
            'placeholder' => 'Bijvoorbeeld: snelle feedback helpt, of ik mis vaste contactmomenten...',
            'order' => 3,
        ],
        [
            'question_id' => 'verbetering',
            'prompt' => 'Welke ene verbetering zou voor jou de grootste impact hebben?',
            'description' => 'Noem de belangrijkste verandering die je graag terugziet.',
            'placeholder' => 'Bijvoorbeeld: duidelijkere planning, meer oefenmateriaal of extra uitleg...',
            'order' => 4,
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
