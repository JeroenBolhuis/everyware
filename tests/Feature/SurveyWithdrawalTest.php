<?php

use App\Models\ContactInformationSubmission;
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

it('deletes related contact information when withdrawing', function () {
    $survey = createWithdrawableSurvey();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'student_email' => 'student@example.com',
        'answers' => [
            $question->id => 'Handige lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->post(route('survey.contact-details.store', $response), [
        'contact_name' => 'Jamie Jansen',
        'contact_email' => 'jamie@example.com',
        'contact_phone' => '+31 6 12345678',
    ])->assertRedirect(route('survey.thankyou', $response));

    $contactSubmission = ContactInformationSubmission::firstOrFail();

    $this->post(route('survey.withdraw.destroy', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('ingetrokken');

    expect(ContactInformationSubmission::find($contactSubmission->id))->toBeNull();

    $response->refresh();

    expect($response->withdrawn_at)->not->toBeNull();
});

it('withdraws successfully without contact information', function () {
    $survey = createWithdrawableSurvey();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'student_email' => 'student@example.com',
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
