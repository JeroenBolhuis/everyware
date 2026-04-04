<?php

namespace App\Mail;

use App\Models\MailRecipient;
use App\Models\SurveyResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveySubmissionConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SurveyResponse $response,
        public MailRecipient $recipient,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('survey-mailing.from.address') ?: config('mail.from.address');
        $fromName = config('survey-mailing.from.name') ?: config('mail.from.name');
        $replyToAddress = config('survey-mailing.reply_to.address');
        $replyToName = config('survey-mailing.reply_to.name');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: $replyToAddress ? [new Address($replyToAddress, $replyToName ?: $fromName)] : [],
            subject: config('survey-mailing.subject', 'Bevestiging van je enquete'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.surveys.submission-confirmation',
        );
    }
}
