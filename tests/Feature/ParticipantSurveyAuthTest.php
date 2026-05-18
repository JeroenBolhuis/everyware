<?php

use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Models\Participant;
use App\Models\Survey;
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

    expect(Participant::where('email', 'nieuw@example.com')->exists())->toBeTrue();

    Mail::assertSent(ParticipantSurveyMagicLinkMail::class, function (ParticipantSurveyMagicLinkMail $mail) {
        return $mail->hasTo('nieuw@example.com')
            && str_contains($mail->signedUrl, 'deelnemer/verify');
    });
});

it('logs a participant in via the signed verify link', function () {
    $participant = Participant::factory()->create(['email' => 'inlog@example.com']);

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
