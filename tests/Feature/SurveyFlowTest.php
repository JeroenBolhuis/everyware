<?php

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyAnswerRetentionSetting;
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

beforeEach(function () {
    loginParticipantAs(Participant::factory()->create(['email' => 'student@example.com']));
});

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
    $participantId = $attributes['participant_id'] ?? auth('participant')->id() ?? Participant::factory()->create()->id;

    return SurveyResponse::create(array_merge([
        'survey_id' => $survey->id,
        'participant_id' => $participantId,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ], $attributes));
}

/* Survey page loads when active */
it('opens the survey page', function () {
    $survey = createSurvey(['is_active' => true]);

    $response = get('/survey/'.$survey->id);

    $response->assertOk();
    $response->assertSee('Are you satisfied?');
    $response->assertSee('Volgende');
});

it('renders stored alt text for swipe images', function () {
    $survey = Survey::factory()->create([
        'title' => 'Swipe Survey',
        'description' => 'Beschrijving',
        'is_active' => true,
    ]);

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Welke afbeelding past beter?',
        'type' => 'swipe',
        'required' => true,
        'options' => [
            [
                'label' => 'Links',
                'image' => 'https://example.com/left.jpg',
                'image_alt' => 'Student geeft presentatie voor de klas',
            ],
            [
                'label' => 'Rechts',
                'image' => 'https://example.com/right.jpg',
                'image_alt' => 'Student werkt samen aan een tafel',
            ],
        ],
        'sort_order' => 1,
    ]);

    $response = get('/survey/'.$survey->id);

    $response->assertOk();
    $response->assertSee('alt="Student geeft presentatie voor de klas"', false);
    $response->assertSee('alt="Student werkt samen aan een tafel"', false);
});

/* Inactive survey returns 404 */
it('returns 404 for inactive survey', function () {
    $survey = createSurvey(['is_active' => false]);

    $response = get('/survey/'.$survey->id);

    $response->assertNotFound();
});

it('submits a survey anonymously without awarding points', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];
    $question2 = $survey->questions[1];

    $response = post('/survey/'.$survey->id, [
        'answers' => [
            $question1->id => 'yes',
            $question2->id => 'Because it works',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
    ]);

    $participant = Participant::where('email', 'student@example.com')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->current_points)->toBe(0)
        ->and($surveyResponse->fresh()->participant_id)->toBe($participant->id)
        ->and($surveyResponse->fresh()->is_anonymous)->toBeTrue();

    Mail::assertNothingSent();
});

it('awards points and sends confirmation when contact is allowed', function () {
    Mail::fake();

    $participant = Participant::factory()->create(['email' => 'ali@example.com']);
    loginParticipantAs($participant);

    $surveyResponse = createSurveyResponse(null, [
        'participant_id' => $participant->id,
    ]);

    post(route('survey.contact-details.store', $surveyResponse))
        ->assertRedirect(route('survey.thankyou', $surveyResponse));

    expect($surveyResponse->fresh()->is_anonymous)->toBeFalse()
        ->and($participant->fresh()->current_points)->toBe(10);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $surveyResponse->id,
    ]);

    Mail::assertSent(SurveySubmissionConfirmationMail::class);
});

it('sets delete_on_date for newly submitted responses when retention is configured', function () {
    SurveyAnswerRetentionSetting::create([
        'auto_delete_after_days' => 14,
    ]);

    $survey = createSurvey();
    $question = $survey->questions[0];

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'yes',
        ],
    ])->assertRedirect();

    $surveyResponse = SurveyResponse::query()->latest('id')->firstOrFail();

    expect($surveyResponse->delete_on_date)->not->toBeNull()
        ->and($surveyResponse->delete_on_date->toDateString())->toBe(now()->addDays(14)->toDateString());
});

it('submits a survey without sending a confirmation email when no email address is provided', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = post('/survey/'.$survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);

    expect($surveyResponse->participant_id)->toBe(auth('participant')->id())
        ->and(Participant::firstWhere('email', 'student@example.com')->current_points)->toBe(0);

    Mail::assertNothingSent();
});

it('silently discards a survey submission when the participant is blocked', function () {
    Mail::fake();

    $participant = Participant::factory()->create([
        'email' => 'ali@example.com',
        'blocked_at' => now(),
    ]);
    loginParticipantAs($participant);

    $survey = createSurvey();
    $question1 = $survey->questions[0];

    post(route('survey.store', $survey), [
        'answers' => [
            $question1->id => 'yes',
        ],
    ])->assertRedirect(route('survey.thankyou.generic'));

    expect(SurveyResponse::count())->toBe(0)
        ->and(ContactInformationSubmission::count())->toBe(0)
        ->and($participant->fresh()->current_points)->toBe(0);

    Mail::assertNothingSent();
});

/* Required questions must be answered */
it('requires answers for required questions', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = from('/survey/'.$survey->id)
        ->post('/survey/'.$survey->id, [
            'answers' => [
                $question1->id => '',
            ],
        ]);

    $response->assertRedirect('/survey/'.$survey->id);
    $response->assertSessionHasErrors([
        "answers.{$question1->id}",
    ]);
});

it('marks the response not anonymous after contact is allowed', function () {
    $participant = Participant::factory()->create(['email' => 'ali@example.com']);
    loginParticipantAs($participant);

    $survey = createSurvey();
    $question1 = $survey->questions[0];

    post('/survey/'.$survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response = post(route('survey.contact-details.store', $surveyResponse));

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    expect($surveyResponse->fresh()->is_anonymous)->toBeFalse()
        ->and($participant->refresh()->current_points)->toBe(10);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $surveyResponse->id,
    ]);
});

it('deletes an existing submission when blocked contact details are added afterwards', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $participant = Participant::create([
        'email' => 'ali@example.com',
    ]);
    loginParticipantAs($participant);

    post(route('survey.store', $survey), [
        'answers' => [
            $question1->id => 'yes',
        ],
    ])->assertRedirect();

    $surveyResponse = SurveyResponse::firstOrFail();
    $participant->block();

    post(route('survey.contact-details.store', $surveyResponse))
        ->assertRedirect(route('survey.thankyou.generic'));

    assertDatabaseMissing('survey_responses', [
        'id' => $surveyResponse->id,
    ]);

    expect(ContactInformationSubmission::count())->toBe(0);
});

/* Empty contact form should not store data */
it('allows contact with the saved participant email', function () {
    $surveyResponse = createSurveyResponse();

    $response = post(route('survey.contact-details.store', $surveyResponse));

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    expect($surveyResponse->fresh()->is_anonymous)->toBeFalse();
});

/* Thank-you page shows shared contact fields */
it('shows shared contact details on the thank you page', function () {
    $surveyResponse = createSurveyResponse();

    $surveyResponse->forceFill(['is_anonymous' => false])->save();

    $response = get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Je inzending is niet anoniem');
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
    ]);

    loginParticipantAs($participant);

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
    $response->assertSee('Ontvang mijn punten');
});

/* Withdrawal page opens with valid token */
it('opens the withdrawal page with a valid token', function () {
    $surveyResponse = createSurveyResponse(null, [
        'withdrawal_token' => 'test-token-123',
    ]);

    $response = get('/survey-withdraw/'.$surveyResponse->withdrawal_token);

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

    $response = post('/survey-withdraw/'.$surveyResponse->withdrawal_token);

    $response->assertOk();

    // Response still exists but is marked withdrawn
    expect($surveyResponse->fresh()->withdrawn_at)->not->toBeNull();

    // Contact data must be removed
    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);
});
