<?php

use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Models\Participant;
use App\Models\Survey;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('redirects a guest from a share link to the participant login with return path', function () {
    $survey = Survey::factory()->active()->create();

    $token = $survey->share_token;

    get(route('survey.share.show', ['token' => $token]))
        ->assertRedirect(route('survey.participant.login', ['redirect' => '/s/'.$token]));
});

it('creates a participant when requesting a magic link for a new email', function () {
    Mail::fake();

    expect(Participant::count())->toBe(0);

    post(route('survey.participant.login.store'), [
        'email' => 'nieuw@example.com',
        'redirect' => '/surveys',
    ])->assertRedirect();

    // Email lives in the personal DB — use the helper to verify it was created.
    expect(participantByEmail('nieuw@example.com'))->not->toBeNull();

    Mail::assertQueued(ParticipantSurveyMagicLinkMail::class, function (ParticipantSurveyMagicLinkMail $mail) {
        return $mail->hasTo('nieuw@example.com')
            && str_contains($mail->signedUrl, 'deelnemer/verify');
    });
});

it('logs a participant in via the signed verify link', function () {
    $participant = Participant::factory()->withEmail('inlog@example.com')->create();

    $signed = URL::temporarySignedRoute(
        'survey.participant.verify',
        now()->addMinutes(5),
        [
            'participant' => $participant->id,
            'redirect' => '/surveys',
        ],
    );

    get($signed)
        ->assertRedirect('/surveys');

    expect(auth('participant')->check())->toBeTrue()
        ->and(auth('participant')->id())->toBe($participant->id);
});

it('rejects a blocked participant signed verify link', function () {
    $participant = Participant::factory()->withEmail('geblokkeerd@example.com')->create([
        'blocked_at' => now(),
    ]);

    $signed = URL::temporarySignedRoute(
        'survey.participant.verify',
        now()->addMinutes(5),
        [
            'participant' => $participant->id,
            'redirect' => '/surveys',
        ],
    );

    get($signed)
        ->assertForbidden();

    expect(auth('participant')->check())->toBeFalse();
});

it('does not cache authenticated survey pages', function () {
    loginParticipantAs(Participant::factory()->create());

    $survey = Survey::factory()->active()->create();

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('redirects stale survey submissions after logout to participant login', function () {
    $this->withMiddleware(ValidateCsrfToken::class);

    $participant = Participant::factory()->create();
    $survey = Survey::factory()->active()->hasQuestions(1)->create();

    loginParticipantAs($participant);

    post(route('survey.participant.logout'), [
        '_token' => csrf_token(),
    ])->assertRedirect(route('survey.participant.login'));

    post(route('survey.store', $survey), [
        'answers' => [
            $survey->questions->first()->id => 'yes',
        ],
        '_token' => 'stale-token',
    ])->assertRedirect(route('survey.participant.login', ['redirect' => '/survey/'.$survey->id]));
});
