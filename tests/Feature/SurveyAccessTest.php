<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Str;

function createActiveSurveyWithQuestion(): Survey
{
    $survey = Survey::factory()->active()->create();

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Hoe gaat het?',
        'type' => 'textarea',
        'required' => true,
        'sort_order' => 1,
    ]);

    return $survey;
}

test('student can access active survey via share token without being logged in', function () {
    $survey = createActiveSurveyWithQuestion();

    $this->get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Wat is je e-mailadres?')
        ->assertSee('naam@student.avans.nl');
});

test('survey url uses share token not numeric id', function () {
    $survey = createActiveSurveyWithQuestion();

    $url = route('survey.show', $survey);

    expect($url)->toContain($survey->share_token)
        ->and($url)->not->toEndWith('/survey/'.$survey->id);
});

test('unknown share token returns 404', function () {
    $this->get('/survey/non-existent-token-xyz')
        ->assertNotFound();
});

test('inactive survey returns 404', function () {
    $survey = Survey::factory()->inactive()->create();

    $this->get(route('survey.show', $survey))
        ->assertNotFound();
});

test('student can submit survey with valid email', function () {
    $survey = createActiveSurveyWithQuestion();
    $question = $survey->questions->first();

    $this->post(route('survey.store', $survey), [
        'student_email' => 'jan@student.avans.nl',
        'answers' => [$question->id => 'Goed!'],
    ])->assertRedirect();

    expect(SurveyResponse::where('student_email', 'jan@student.avans.nl')->exists())->toBeTrue();
});

test('student cannot submit survey twice with the same email', function () {
    $survey = createActiveSurveyWithQuestion();
    $question = $survey->questions->first();

    SurveyResponse::create([
        'survey_id' => $survey->id,
        'student_email' => 'jan@student.avans.nl',
        'withdrawal_token' => Str::uuid(),
        'submitted_at' => now(),
    ]);

    $this->post(route('survey.store', $survey), [
        'student_email' => 'jan@student.avans.nl',
        'answers' => [$question->id => 'Goed!'],
    ])->assertSessionHasErrors('student_email');
});

test('survey submission requires a valid email address', function () {
    $survey = createActiveSurveyWithQuestion();
    $question = $survey->questions->first();

    $this->post(route('survey.store', $survey), [
        'student_email' => 'geen-email',
        'answers' => [$question->id => 'Goed!'],
    ])->assertSessionHasErrors('student_email');
});

test('survey submission requires email to be present', function () {
    $survey = createActiveSurveyWithQuestion();
    $question = $survey->questions->first();

    $this->post(route('survey.store', $survey), [
        'answers' => [$question->id => 'Goed!'],
    ])->assertSessionHasErrors('student_email');
});
