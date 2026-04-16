<?php

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

// Reset database between tests
uses(RefreshDatabase::class);

/* Create a test survey with 2 questions */
function createSurvey(array $attributes = []): Survey
{
    $survey = Survey::factory()->create(array_merge([
        'title' => 'Test Survey',
        'description' => 'Test description',
        'is_active' => true,
    ], $attributes));

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Are you satisfied?',
        'type' => 'radio',
        'required' => true,
        'options' => ['yes', 'no'],
        'sort_order' => 1,
    ]);

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Why?',
        'type' => 'textarea',
        'required' => false,
        'options' => null,
        'sort_order' => 2,
    ]);

    return $survey->fresh('questions');
}

/* Create a survey response manually */
function createSurveyResponse(?Survey $survey = null, array $attributes = []): SurveyResponse
{
    $survey ??= createSurvey();

    return SurveyResponse::create(array_merge([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ], $attributes));
}

/* Survey page loads when active */
it('opens the survey page', function () {
    $survey = createSurvey(['is_active' => true]);

    $response = get('/survey/' . $survey->id);

    $response->assertOk();
    $response->assertSee('Are you satisfied?');
    $response->assertSee('Volgende');
});

/* Inactive survey returns 404 */
it('returns 404 for inactive survey', function () {
    $survey = createSurvey(['is_active' => false]);

    $response = get('/survey/' . $survey->id);

    $response->assertNotFound();
});

it('submits a survey and sends a confirmation email when an email address is provided', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];
    $question2 = $survey->questions[1];

    $response = post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
            $question2->id => 'Because it works',
        ],
        'contact_name' => 'Ali Test',
        'contact_email' => 'Ali@Example.com',
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
    ]);

    assertDatabaseHas('contact_information_submissions', [
        'survey_id' => $survey->id,
        'survey_response_id' => $surveyResponse->id,
    ]);

    $contactSubmission = ContactInformationSubmission::where('survey_response_id', $surveyResponse->id)->first();

    expect($contactSubmission)->not->toBeNull()
        ->and($contactSubmission->name)->toBe('Ali Test')
        ->and($contactSubmission->email)->toBe('ali@example.com');

    Mail::assertSent(SurveySubmissionConfirmationMail::class, function (SurveySubmissionConfirmationMail $mail) use ($surveyResponse) {
        return $mail->response->is($surveyResponse)
            && $mail->recipientName === 'Ali Test'
            && $mail->hasTo('ali@example.com');
    });

    $participant = Participant::where('email', 'ali@example.com')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->current_points)->toBe(10)
        ->and($surveyResponse->fresh()->participant_id)->toBe($participant->id);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $surveyResponse->id,
    ]);

    Mail::assertSent(SurveySubmissionConfirmationMail::class, function (SurveySubmissionConfirmationMail $mail) {
        $rendered = $mail->render();

        return str_contains($rendered, '10 punten')
            && str_contains($rendered, 'Je totaal staat nu op')
            && str_contains($rendered, '10 punten');
    });
});

it('submits a survey without sending a confirmation email when no email address is provided', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
        'contact_name' => '',
        'contact_email' => '',
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);

    Mail::assertNothingSent();
});

/* Required questions must be answered */
it('requires answers for required questions', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = from('/survey/' . $survey->id)
        ->post('/survey/' . $survey->id, [
            'answers' => [
                $question1->id => '',
            ],
        ]);

    $response->assertRedirect('/survey/' . $survey->id);
    $response->assertSessionHasErrors([
        "answers.{$question1->id}",
    ]);
});

/* Contact details can be stored encrypted */
it('saves contact details after submission', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response = post('/survey/response/' . $surveyResponse->id . '/contact-details', [
        'contact_name' => 'Ali Test',
        'contact_email' => 'Ali@Example.com',
        'contact_phone' => '06 12345678',
    ]);

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseHas('contact_information_submissions', [
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
    ]);

    $contactSubmission = ContactInformationSubmission::where('survey_response_id', $surveyResponse->id)->first();

    expect($contactSubmission->name)->toBe('Ali Test');
    expect($contactSubmission->email)->toBe('ali@example.com');
    expect($contactSubmission->phone)->toBe('0612345678');
    expect($contactSubmission->getRawOriginal('email'))->not->toBe('ali@example.com');

    $participant = Participant::where('email', 'ali@example.com')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->current_points)->toBe(10);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $surveyResponse->id,
    ]);
});

/* Empty contact form should not store data */
it('skips saving contact details when all fields are empty', function () {
    $surveyResponse = createSurveyResponse();

    $response = post('/survey/response/' . $surveyResponse->id . '/contact-details', [
        'contact_name' => '',
        'contact_email' => '',
        'contact_phone' => '',
    ]);

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);
});

/* Thank-you page shows shared contact fields */
it('shows shared contact details on the thank you page', function () {
    $surveyResponse = createSurveyResponse();

    ContactInformationSubmission::create([
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
        'name' => 'Ali Test',
        'email' => 'ali@example.com',
        'phone' => null,
    ]);

    $response = get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Je hebt contactgegevens gedeeld');
});

it('shows the mail confirmation state on the thank you page', function () {
    $surveyResponse = createSurveyResponse();

    $response = $this->withSession(['confirmationMailStatus' => 'sent'])
        ->get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Er is een bevestigingsmail verstuurd.');
});

it('shows the awarded and total points on the thank you page', function () {
    $participant = Participant::create([
        'email' => 'ali@example.com',
        'name' => 'Ali Test',
    ]);

    $participant->forceFill(['current_points' => 10])->save();

    $surveyResponse = createSurveyResponse(null, [
        'participant_id' => $participant->id,
    ]);

    ParticipantPointsHistory::create([
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $surveyResponse->id,
    ]);

    $response = get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Je hebt 10 punten gekregen.');
    $response->assertSee('Je totaal staat nu op 10 punten.');
});

/* Thank-you page shows form if no contact data */
it('shows the contact form on the thank you page when no contact details exist', function () {
    $surveyResponse = createSurveyResponse();

    $response = get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Contactgegevens opslaan');
});

/* Withdrawal page opens with valid token */
it('opens the withdrawal page with a valid token', function () {
    $surveyResponse = createSurveyResponse(null, [
        'withdrawal_token' => 'test-token-123',
    ]);

    $response = get('/survey-withdraw/' . $surveyResponse->withdrawal_token);

    $response->assertOk();
});

/* Withdrawal removes contact info and marks response */
it('removes contact details and marks the response as withdrawn', function () {
    $surveyResponse = createSurveyResponse(null, [
        'withdrawal_token' => 'test-token-123',
        'withdrawn_at' => null,
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
        'name' => 'Ali Test',
        'email' => 'ali@example.com',
        'phone' => '0612345678',
    ]);

    $response = post('/survey-withdraw/' . $surveyResponse->withdrawal_token);

    $response->assertOk();

    // Response still exists but is marked withdrawn
    expect($surveyResponse->fresh()->withdrawn_at)->not->toBeNull();

    // Contact data must be removed
    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);
});
