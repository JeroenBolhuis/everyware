<?php

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    loginParticipantAs(Participant::factory()->create());
});

function createSurveyWithQuestion(): Survey
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

it('does not show the thank-you contact form on the survey page', function () {
    $survey = createSurveyWithQuestion();

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Wat vind je van deze module?')
        ->assertSee('Deze enquête kun je anoniem invullen.')
        ->assertDontSee('Contactgegevens opslaan');
});

it('shows only the name contact field on the thank you page for logged-in participants', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Contactgegevens')
        ->assertSee('Naam')
        ->assertDontSee('E-mailadres')
        ->assertSee('Je enquête is al anoniem opgeslagen.')
        ->assertSee('Contactgegevens opslaan');
});

it('submits survey without contact details', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::first();

    expect($response)->not->toBeNull();
    expect(ContactInformationSubmission::count())->toBe(0);

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Wil je dat we contact met je opnemen? Vul hieronder je naam in.');
});

it('stores encrypted contact details using the logged-in participant email', function () {
    $participant = Participant::factory()->create(['email' => 'jamie@example.com']);
    loginParticipantAs($participant);

    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Erg nuttig.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_name' => 'Jamie Jansen',
        ])
        ->assertRedirect(route('survey.thankyou', $response));

    $contactSubmission = ContactInformationSubmission::firstOrFail();

    expect($contactSubmission->survey_id)->toBe($survey->id)
        ->and($contactSubmission->survey_response_id)->toBe($response->id)
        ->and($contactSubmission->name)->toBe('Jamie Jansen')
        ->and($contactSubmission->email)->toBe('jamie@example.com')
        ->and($contactSubmission->phone)->toBeNull();

    expect($contactSubmission->getRawOriginal('name'))->not->toBe('Jamie Jansen');
    expect($contactSubmission->getRawOriginal('email'))->not->toBe('jamie@example.com');

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Je hebt contactgegevens gedeeld.')
        ->assertSee('Naam opgeslagen')
        ->assertSee('E-mailadres opgeslagen')
        ->assertSee('versleuteld opgeslagen');
});

it('sends a confirmation email after contact details are saved on the thank you page', function () {
    Mail::fake();

    $participant = Participant::factory()->create(['email' => 'jamie@example.com']);
    loginParticipantAs($participant);

    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Erg nuttig.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_name' => 'Jamie Jansen',
        ])
        ->assertRedirect(route('survey.thankyou', $response));

    Mail::assertSent(SurveySubmissionConfirmationMail::class, function (SurveySubmissionConfirmationMail $mail) use ($response) {
        return $mail->response->is($response)
            && $mail->recipientName === 'Jamie Jansen'
            && $mail->hasTo('jamie@example.com');
    });
});

it('validates optional phone details when provided', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Goede lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_phone' => 'abc',
        ])
        ->assertRedirect(route('survey.thankyou', $response))
        ->assertSessionHasErrors(['contact_phone']);

    expect(ContactInformationSubmission::count())->toBe(0);
});
