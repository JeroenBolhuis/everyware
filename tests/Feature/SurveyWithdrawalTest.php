<?php

use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;

function createWithdrawableSurvey(): Survey
{
    $survey = Survey::factory()->active()->create();

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Wat vind je van deze module?',
        'type' => 'textarea',
        'options' => null,
        'required' => true,
        'sort_order' => 1,
    ]);

    return $survey;
}

beforeEach(function () {
    loginParticipantAs(Participant::factory()->create());
});

it('keeps the response withdrawable after contact is allowed', function () {
    $survey = createWithdrawableSurvey();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Handige lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->post(route('survey.contact-details.store', $response))
        ->assertRedirect(route('survey.thankyou', $response));

    expect($response->fresh()->is_anonymous)->toBeFalse();

    $this->post(route('survey.withdraw.destroy', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('ingetrokken');

    $response->refresh();

    expect($response->withdrawn_at)->not->toBeNull();
});

it('withdraws successfully without contact information', function () {
    $survey = createWithdrawableSurvey();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->post(route('survey.withdraw.destroy', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('ingetrokken');

    $response->refresh();

    expect($response->withdrawn_at)->not->toBeNull()
        ->and(ContactInformationSubmission::count())->toBe(0);
});
