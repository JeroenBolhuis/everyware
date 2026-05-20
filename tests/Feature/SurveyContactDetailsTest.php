<?php

use App\Mail\SurveySubmissionConfirmationMail;
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

it('shows the contact permission button on the thank you page for logged-in participants', function () {
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
        ->assertSee('Anonimiteit')
        ->assertSee('LIC-medewerkers mogen mij benaderen')
        ->assertDontSee('E-mailadres')
        ->assertSee('Je inzending is anoniem opgeslagen.');
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
    expect($response->is_anonymous)->toBeTrue();

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Je inzending is anoniem opgeslagen.');
});

it('marks a response not anonymous using the logged-in participant email', function () {
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
        ->post(route('survey.contact-details.store', $response))
        ->assertRedirect(route('survey.thankyou', $response));

    expect($response->fresh()->is_anonymous)->toBeFalse()
        ->and($response->fresh()->participant->email)->toBe('jamie@example.com');

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Je inzending is niet anoniem')
        ->assertSee('E-mailadres zichtbaar voor LIC');
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
        ->post(route('survey.contact-details.store', $response))
        ->assertRedirect(route('survey.thankyou', $response));

    Mail::assertSent(SurveySubmissionConfirmationMail::class, function (SurveySubmissionConfirmationMail $mail) use ($response) {
        return $mail->response->is($response)
            && $mail->recipientName === null
            && $mail->hasTo('jamie@example.com');
    });
});

it('does not allow a different participant to reveal a response', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Goede lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    loginParticipantAs(Participant::factory()->create());

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response))
        ->assertForbidden();

    expect($response->fresh()->is_anonymous)->toBeTrue();
});
