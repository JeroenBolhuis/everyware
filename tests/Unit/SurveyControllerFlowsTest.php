<?php

use App\Http\Controllers\ParticipantSurveyAuthController;
use App\Http\Controllers\StudentPointsController;
use App\Livewire\Actions\Logout;
use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\View\Components\Layout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createControllerFlowResponse(Survey $survey, Participant $participant, array $attributes = []): SurveyResponse
{
    return SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => $attributes['is_anonymous'] ?? true,
        'withdrawal_token' => $attributes['withdrawal_token'] ?? (string) Str::uuid(),
        'submitted_at' => $attributes['submitted_at'] ?? now(),
        'withdrawn_at' => $attributes['withdrawn_at'] ?? null,
    ]);
}

it('renders the layout component view', function () {
    expect((new Layout)->render()->name())->toBe('components.layout');
});

it('logs out web users through the Livewire logout action', function () {
    $this->actingAs(User::factory()->createOne());

    $response = (new Logout)();

    expect(Auth::guard('web')->check())->toBeFalse()
        ->and($response->getTargetUrl())->toBe(url('/'));
});

it('shows participant login with a sanitized redirect path', function () {
    $request = Request::create('/survey/deelnemer/inloggen', 'GET', [
        'redirect' => 'https://evil.example/steal',
    ]);

    $response = app(ParticipantSurveyAuthController::class)->create($request);

    expect($response->name())->toBe('surveys.participant-login')
        ->and($response->getData()['redirect'])->toBe('/surveys');
});

it('sends participant magic links to unblocked participants', function () {
    Mail::fake();

    $response = $this->post(route('survey.participant.login.store'), [
        'email' => '  JAMIE@EXAMPLE.COM  ',
        'redirect' => '/student/punten',
    ]);

    $response->assertSessionHas('magicLinkStatus', 'sent');
    // Email is now in the personal DB — look it up via the service.
    expect(participantByEmail('jamie@example.com'))->not->toBeNull();
    Mail::assertSent(ParticipantSurveyMagicLinkMail::class);
});

it('does not send magic links to blocked participants', function () {
    Mail::fake();

    Participant::factory()->withEmail('blocked@example.com')->create([
        'blocked_at' => now(),
    ]);

    $this->post(route('survey.participant.login.store'), [
        'email' => 'blocked@example.com',
    ])->assertSessionHas('magicLinkStatus', 'sent');

    Mail::assertNotSent(ParticipantSurveyMagicLinkMail::class);
});

it('verifies signed participant login links and regenerates the session', function () {
    $participant = Participant::factory()->createOne();
    $signedUrl = URL::temporarySignedRoute('survey.participant.verify', now()->addMinutes(10), [
        'participant' => $participant->id,
        'redirect' => '/student/punten',
    ]);

    $this->get($signedUrl)->assertRedirect('/student/punten');

    expect(Auth::guard('participant')->check())->toBeTrue();
});

it('rejects invalid participant verification signatures', function () {
    $participant = Participant::factory()->createOne();

    $this->get(route('survey.participant.verify', [
        'participant' => $participant->id,
        'redirect' => '/student/punten',
    ]))->assertForbidden();
});

it('shows active surveys and hides expired surveys from the public index', function () {
    $visibleSurvey = Survey::factory()->active()->createOne([
        'title' => 'Zichtbare enquete',
        'ends_at' => today()->addDay(),
    ]);
    SurveyQuestion::factory()->createOne(['survey_id' => $visibleSurvey->id]);

    Survey::factory()->active()->createOne([
        'title' => 'Verlopen enquete',
        'ends_at' => today()->subDay(),
    ]);

    $response = $this->get(route('surveys.index', ['search' => 'enquete']));

    $response->assertOk()
        ->assertSee('Zichtbare enquete')
        ->assertDontSee('Verlopen enquete');
});

it('renders responsive survey index actions without oversized desktop buttons', function () {
    $survey = Survey::factory()->active()->createOne([
        'title' => 'Responsieve enquete',
        'ends_at' => today()->addDay(),
        'reward_points' => 25,
    ]);
    SurveyQuestion::factory()->createOne(['survey_id' => $survey->id]);

    $this->get(route('surveys.index'))
        ->assertOk()
        ->assertDontSee('Zoeken op titel')
        ->assertSee('btn-secondary w-full sm:w-auto sm:flex-none', false)
        ->assertSee('btn-primary w-full text-center sm:w-auto sm:min-w-40 md:w-full md:min-w-0 lg:max-w-none', false)
        ->assertSee('Beloning')
        ->assertSee('25')
        ->assertSee('punten');
});

it('sorts completed surveys below fillable surveys', function () {
    $participant = Participant::factory()->createOne();
    $openSurvey = Survey::factory()->active()->createOne([
        'title' => 'Nog open enquete',
        'created_at' => now()->subDay(),
    ]);
    $completedSurvey = Survey::factory()->active()->createOne([
        'title' => 'Al ingevulde enquete',
        'created_at' => now(),
        'reward_points' => 100,
    ]);

    SurveyQuestion::factory()->createOne(['survey_id' => $openSurvey->id]);
    SurveyQuestion::factory()->createOne(['survey_id' => $completedSurvey->id]);
    createControllerFlowResponse($completedSurvey, $participant);

    $this->actingAs($participant, 'participant')
        ->get(route('surveys.index', ['sort' => 'reward_points_desc']))
        ->assertOk()
        ->assertSeeInOrder(['Nog open enquete', 'Al ingevulde enquete']);
});

it('shows a survey by share token for authenticated participants', function () {
    $participant = Participant::factory()->createOne();
    $survey = Survey::factory()->active()->createOne([
        'share_token' => 'public-token',
    ]);
    SurveyQuestion::factory()->createOne(['survey_id' => $survey->id]);

    $this->actingAs($participant, 'participant')
        ->get(route('survey.share.show', 'public-token'))
        ->assertOk()
        ->assertSee($survey->questions()->first()->question);
});

it('stores survey responses and redirects duplicate submissions to already completed', function () {
    $participant = Participant::factory()->createOne();
    $survey = Survey::factory()->active()->createOne();
    $question = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'required' => true,
    ]);

    $firstResponse = $this->actingAs($participant, 'participant')
        ->post(route('survey.store', $survey), [
            'answers' => [
                $question->id => 'Mijn antwoord',
            ],
        ]);

    $storedResponse = SurveyResponse::query()->firstOrFail();
    $firstResponse->assertRedirect(route('survey.thankyou', $storedResponse));

    $this->actingAs($participant, 'participant')
        ->post(route('survey.store', $survey), [
            'answers' => [
                $question->id => 'Nieuw antwoord',
            ],
        ])
        ->assertRedirect(route('survey.already-completed', $storedResponse));
});

it('allows participants to share contact details and receive points once', function () {
    Mail::fake();

    $participant = Participant::factory()->withEmail('student@example.com')->createOne();
    $survey = Survey::factory()->active()->createOne([
        'reward_points' => 12,
    ]);
    $response = createControllerFlowResponse($survey, $participant);

    $this->actingAs($participant, 'participant')
        ->post(route('survey.contact-details.store', $response))
        ->assertRedirect(route('survey.thankyou', $response))
        ->assertSessionHas('contactAllowed', true)
        ->assertSessionHas('confirmationMailStatus', 'sent');

    expect($response->fresh()->is_anonymous)->toBeFalse()
        ->and($participant->fresh()->current_points)->toBe(12)
        ->and($response->participantPointsHistories()->count())->toBe(1);

    Mail::assertSent(SurveySubmissionConfirmationMail::class);
});

it('deletes responses instead of allowing contact details for blocked participants', function () {
    $participant = Participant::factory()->withEmail('blocked-again@example.com')->createOne([
        'blocked_at' => now(),
    ]);
    $survey = Survey::factory()->active()->createOne();
    $response = createControllerFlowResponse($survey, $participant);

    $this->actingAs($participant, 'participant')
        ->post(route('survey.contact-details.store', $response))
        ->assertRedirect(route('survey.thankyou.generic'));

    expect(SurveyResponse::query()->whereKey($response->id)->exists())->toBeFalse();
});

it('shows participant points and survey withdrawal screens', function () {
    $participant = Participant::factory()->createOne();
    $survey = Survey::factory()->active()->createOne();
    $response = createControllerFlowResponse($survey, $participant);

    $pointsRequest = Request::create('/student/punten');
    $pointsRequest->setUserResolver(fn (?string $guard = null) => $guard === 'participant' ? $participant : null);

    $pointsView = (new StudentPointsController)($pointsRequest);

    expect($pointsView->name())->toBe('student.points')
        ->and($pointsView->getData()['participant']->is($participant))->toBeTrue()
        ->and($pointsView->getData()['responses'])->toHaveCount(1);

    $this->get(route('survey.withdraw.show', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('Toegang intrekken');
});

it('marks survey responses as withdrawn and removes contact submissions', function () {
    $participant = Participant::factory()->createOne();
    $survey = Survey::factory()->active()->createOne();
    $response = createControllerFlowResponse($survey, $participant);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $response->id,
        'email' => 'student@example.com',
    ]);

    $this->post(route('survey.withdraw.destroy', $response->withdrawal_token))
        ->assertOk()
        ->assertSee('ingetrokken');

    expect($response->fresh()->withdrawn_at)->not->toBeNull()
        ->and(ContactInformationSubmission::query()->where('survey_response_id', $response->id)->exists())->toBeFalse();
});

it('exports survey feedback for admins', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = Survey::factory()->createOne([
        'title' => 'Export enquete',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.surveys.export', [$survey, 'format' => 'csv']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="survey-feedback-export-enquete.csv"');
});
