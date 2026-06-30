<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ParticipantSurveyMagicLinkMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public string $signedUrl,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Participant survey magic link delivery failed.', [
            'exception_class' => $exception::class,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Log in bij de enquête',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.surveys.participant-magic-link',
        );
    }
}
