<?php

use App\Models\ContactInformationSubmission;
use App\Models\MailRecipient;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Mail;

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

it('deletes related contact information and revokes stored mail recipient data when withdrawing', function () {
    Mail::fake();

    $survey = createWithdrawableSurvey();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Handige lesstof.',
        ],
        'contact_name' => 'Jamie Jansen',
        'contact_email' => 'jamie@example.com',
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->post(route('survey.contact-details.store', $response), [
        'contact_name' => 'Jamie Jansen',
        'contact_email' => 'jamie@example.com',
        'contact_phone' => '+31 6 12345678',
    ])->assertRedirect(route('survey.thankyou', $response));

    $contactSubmission = ContactInformationSubmission::firstOrFail();
    $mailRecipient = MailRecipient::firstOrFail();

    $this->post(route('survey.withdraw.destroy', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('ingetrokken');

    expect(ContactInformationSubmission::find($contactSubmission->id))->toBeNull();

    $response->refresh();
    $mailRecipient->refresh();

    expect($response->withdrawn_at)->not->toBeNull()
        ->and($mailRecipient->revoked_at)->not->toBeNull()
        ->and($mailRecipient->full_name_encrypted)->toBeNull()
        ->and($mailRecipient->email_encrypted)->toBeNull()
        ->and($mailRecipient->email_hash)->toBeNull();
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
        ->and(ContactInformationSubmission::count())->toBe(0)
        ->and(MailRecipient::count())->toBe(0);
});
