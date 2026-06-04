<?php

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\QueryException;
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
    loginParticipantAs(Participant::factory()->withEmail('student@example.com')->create([
        'onboarded_at' => now(),
    ]));
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
    $response->assertSee('/images/Avans_Hogeschool_Logo.png', false);
    $response->assertSee('Are you satisfied?');
    $response->assertSee('Volgende');
});

it('shows submit text on a survey with a single question', function () {
    $survey = Survey::factory()->create([
        'title' => 'Korte survey',
        'is_active' => true,
    ]);

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Is dit duidelijk?',
        'type' => 'radio',
        'required' => true,
        'options' => ['Ja', 'Nee'],
        'sort_order' => 1,
    ]);

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Verzenden')
        ->assertDontSee('Volgende');
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

it('shows a clear message when the survey end date has passed', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);

    get(route('survey.show', $survey))
        ->assertStatus(410)
        ->assertSee('Deze enquête kan niet meer worden ingevuld.');
});

it('does not accept submissions after the survey end date has passed', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);
    $question = $survey->questions[0];

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'yes',
        ],
    ])
        ->assertStatus(410)
        ->assertSee('Deze enquête kan niet meer worden ingevuld.');

    assertDatabaseMissing('survey_responses', [
        'survey_id' => $survey->id,
    ]);
});

it('shows the expired message before answer validation on expired surveys', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);

    post(route('survey.store', $survey), [])
        ->assertStatus(410)
        ->assertSee('Deze enquête kan niet meer worden ingevuld.');

    assertDatabaseMissing('survey_responses', [
        'survey_id' => $survey->id,
    ]);
});

it('shows the expired message before answer validation on expired shared survey links', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);

    post(route('survey.share.store', $survey->share_token), [])
        ->assertStatus(410)
        ->assertSee('Deze enquête kan niet meer worden ingevuld.');

    assertDatabaseMissing('survey_responses', [
        'survey_id' => $survey->id,
    ]);
});

it('keeps surveys without an end date fillable while active', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => null,
    ]);

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Are you satisfied?');
});

it('keeps surveys fillable on their end date', function () {
    $survey = createSurvey([
        'is_active' => true,
        'ends_at' => today(),
    ]);

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Are you satisfied?');
});

it('only shows fillable surveys on the public survey overview', function () {
    $active = createSurvey([
        'title' => 'Actieve survey',
        'is_active' => true,
        'ends_at' => today()->addDays(3),
    ]);
    createSurvey([
        'title' => 'Gesloten survey',
        'is_active' => false,
    ]);
    createSurvey([
        'title' => 'Verlopen survey',
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee($active->title)
        ->assertSee('Einddatum: '.today()->addDays(3)->format('d-m-Y'))
        ->assertDontSee('Gesloten survey')
        ->assertDontSee('Verlopen survey');
});

it('only shows academy targeted surveys to matching participants', function () {
    $generalSurvey = createSurvey([
        'title' => 'Algemene survey',
        'is_active' => true,
        'target_academy' => null,
    ]);
    $avansSurvey = createSurvey([
        'title' => 'Avans survey',
        'is_active' => true,
        'target_academy' => 'avans',
    ]);
    $fontysSurvey = createSurvey([
        'title' => 'Fontys survey',
        'is_active' => true,
        'target_academy' => 'fontys',
    ]);
    $huSurvey = createSurvey([
        'title' => 'HU survey',
        'is_active' => true,
        'target_academy' => 'hogeschool-utrecht',
    ]);

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee($generalSurvey->title)
        ->assertDontSee($avansSurvey->title)
        ->assertDontSee($fontysSurvey->title)
        ->assertDontSee($huSurvey->title);

    loginParticipantAs(Participant::factory()->withEmail('student@student.avans.nl')->create([
        'onboarded_at' => now(),
    ]));

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee($generalSurvey->title)
        ->assertSee($avansSurvey->title)
        ->assertDontSee($fontysSurvey->title)
        ->assertDontSee($huSurvey->title);

    loginParticipantAs(Participant::factory()->withEmail('student@student.fontys.nl')->create([
        'onboarded_at' => now(),
    ]));

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee($generalSurvey->title)
        ->assertDontSee($avansSurvey->title)
        ->assertSee($fontysSurvey->title)
        ->assertDontSee($huSurvey->title);

    loginParticipantAs(Participant::factory()->withEmail('student@student.hu.nl')->create([
        'onboarded_at' => now(),
    ]));

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee($generalSurvey->title)
        ->assertDontSee($avansSurvey->title)
        ->assertDontSee($fontysSurvey->title)
        ->assertSee($huSurvey->title);
});

it('shows a clear ineligible page to non matching participants for academy targeted surveys', function () {
    $survey = createSurvey([
        'title' => 'Alleen Avans',
        'is_active' => true,
        'target_academy' => 'avans',
    ]);
    $question = $survey->questions[0];

    get(route('survey.show', $survey))
        ->assertForbidden()
        ->assertSee('Je bent niet geschikt om deze enquete in te vullen.')
        ->assertSee('Alleen Avans');

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'yes',
        ],
    ])
        ->assertForbidden()
        ->assertSee('Je bent niet geschikt om deze enquete in te vullen.');

    assertDatabaseMissing('survey_responses', [
        'survey_id' => $survey->id,
    ]);
});

it('allows matching academy participants to fill targeted surveys', function () {
    loginParticipantAs(Participant::factory()->withEmail('student@avans.nl')->create([
        'onboarded_at' => now(),
    ]));

    $survey = createSurvey([
        'title' => 'Avans invullen',
        'is_active' => true,
        'target_academy' => 'avans',
    ]);
    $question = $survey->questions[0];

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Are you satisfied?');

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'yes',
        ],
    ])->assertRedirect();

    assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
        'participant_id' => auth('participant')->id(),
    ]);
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

    $participant = participantByEmail('student@example.com');

    expect($participant)->not->toBeNull()
        ->and($participant->current_points)->toBe(0)
        ->and($surveyResponse->fresh()->participant_id)->toBe($participant->id)
        ->and($surveyResponse->fresh()->is_anonymous)->toBeTrue();

    Mail::assertNothingSent();
});

it('shows an already completed screen when the participant opens a completed survey', function () {
    $survey = createSurvey();
    createSurveyResponse($survey, [
        'participant_id' => auth('participant')->id(),
    ]);

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Je hebt deze enquête al ingevuld')
        ->assertSee('Mijn punten bekijken')
        ->assertSee('Naar alle enquêtes')
        ->assertDontSee('Are you satisfied?');
});

it('prevents duplicate survey submissions on the backend', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    createSurveyResponse($survey, [
        'participant_id' => auth('participant')->id(),
    ]);

    post(route('survey.store', $survey), [
        'answers' => [
            $question1->id => 'yes',
        ],
    ])->assertRedirect(route('survey.already-completed', SurveyResponse::firstOrFail()));

    expect(SurveyResponse::query()->where('survey_id', $survey->id)->count())->toBe(1);
});

it('marks completed surveys as disabled on the survey overview', function () {
    $completedSurvey = createSurvey(['title' => 'Al ingevulde enquête']);
    $openSurvey = createSurvey(['title' => 'Nieuwe enquête']);

    createSurveyResponse($completedSurvey, [
        'participant_id' => auth('participant')->id(),
    ]);

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee('Al ingevulde enquête')
        ->assertSee('Nieuwe enquête')
        ->assertSee('Enquête al ingevuld')
        ->assertSee('Enquête invullen');
});

it('enforces one response per participant and survey in the database', function () {
    $survey = createSurvey();
    $participant = Participant::factory()->create();

    createSurveyResponse($survey, [
        'participant_id' => $participant->id,
    ]);

    createSurveyResponse($survey, [
        'participant_id' => $participant->id,
    ]);
})->throws(QueryException::class);

it('awards points and sends confirmation when contact is allowed', function () {
    Mail::fake();

    $participant = Participant::factory()->withEmail('ali@example.com')->create();
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

it('stores submitted_at for retention processing on new submissions', function () {
    $survey = createSurvey();
    $question = $survey->questions[0];

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'yes',
        ],
    ])->assertRedirect();

    $surveyResponse = SurveyResponse::query()->latest('id')->firstOrFail();

    expect($surveyResponse->submitted_at)->not->toBeNull();
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
    ], 'personal');

    expect($surveyResponse->participant_id)->toBe(auth('participant')->id())
        ->and(participantByEmail('student@example.com')?->current_points)->toBe(0);

    Mail::assertNothingSent();
});

it('silently discards a survey submission when the participant is blocked', function () {
    Mail::fake();

    $participant = Participant::factory()->withEmail('ali@example.com')->create([
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
    $participant = Participant::factory()->withEmail('ali@example.com')->create();
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

    $participant = Participant::factory()->withEmail('ali@example.com')->create();
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
    $participant = Participant::factory()->withEmail('ali@example.com')->create();

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
    $response->assertSee('Mijn punten bekijken');
    $response->assertSee('Naar alle enquêtes');
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
    ], 'personal');
});
