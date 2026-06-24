<?php

use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds the participant magic link mail metadata', function () {
    $mail = new ParticipantSurveyMagicLinkMail('https://example.test/surveys/token');

    expect($mail->signedUrl)->toBe('https://example.test/surveys/token')
        ->and($mail->envelope()->subject)->toBe('Log in bij de enquête')
        ->and($mail->content()->markdown)->toBe('emails.surveys.participant-magic-link')
        ->and($mail)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail)->toBeInstanceOf(ShouldQueueAfterCommit::class)
        ->and($mail)->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($mail->tries)->toBe(3)
        ->and($mail->timeout)->toBe(30)
        ->and($mail->backoff())->toBe([60, 300, 900]);
});

it('explains why students receive the participant magic link mail', function () {
    $mail = new ParticipantSurveyMagicLinkMail('https://example.test/surveys/token');

    $mail->assertSeeInHtml('Je ontvangt deze mail omdat jouw e-mailadres is ingevuld om in te loggen bij Everyware');
    $mail->assertSeeInHtml('Heb je deze loginlink niet aangevraagd?');
    $mail->assertSeeInText('Je ontvangt deze mail omdat jouw e-mailadres is ingevuld om in te loggen bij Everyware');
    $mail->assertSeeInText('Heb je deze loginlink niet aangevraagd?');
});

it('logs a final participant magic link delivery failure without sensitive mail data', function () {
    Log::spy();

    $exception = new RuntimeException('Mail transport unavailable.');
    $mail = new ParticipantSurveyMagicLinkMail('https://example.test/surveys/sensitive-token');

    $mail->failed($exception);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Participant survey magic link delivery failed.', [
            'exception_class' => RuntimeException::class,
        ]);
});

it('builds the survey submission confirmation mail metadata', function () {
    $survey = Survey::factory()->createOne();
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => Participant::factory()->createOne()->id,
        'is_anonymous' => false,
        'withdrawal_token' => (string) Str::uuid(),
    ]);

    $mail = new SurveySubmissionConfirmationMail($response, 'Jamie');

    expect($mail->response->is($response))->toBeTrue()
        ->and($mail->recipientName)->toBe('Jamie')
        ->and($mail->envelope()->subject)->toBe('Bevestiging van je enquete')
        ->and($mail->content()->markdown)->toBe('emails.surveys.submission-confirmation');
});
