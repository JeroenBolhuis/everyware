<?php

use Livewire\Livewire;

test('student survey page is displayed', function () {
    $this->get(route('student.survey.show'))
        ->assertOk()
        ->assertSee('Studentenenquete');
});

test('first question shows a visible progress bar', function () {
    Livewire::test('pages::student.survey')
        ->assertSeeHtml('data-test="survey-progress-bar"')
        ->assertSee('Vraag 1 van 4')
        ->assertSee('25% voltooid')
        ->assertSeeHtml('style="width: 25%;"');
});

test('progress updates when moving to the next question', function () {
    Livewire::test('pages::student.survey')
        ->call('nextQuestion')
        ->assertSet('currentQuestionIndex', 1)
        ->assertSee('Vraag 2 van 4')
        ->assertSee('50% voltooid')
        ->assertSeeHtml('style="width: 50%;"');
});

test('progress remains visible and correct when moving to the previous question', function () {
    Livewire::test('pages::student.survey')
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
    Livewire::test('pages::student.survey')
        ->call('nextQuestion')
        ->call('nextQuestion')
        ->call('nextQuestion')
        ->assertSet('currentQuestionIndex', 3)
        ->assertSee('Vraag 4 van 4')
        ->assertSee('100% voltooid')
        ->assertSeeHtml('style="width: 100%;"');
});
