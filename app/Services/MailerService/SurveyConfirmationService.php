<?php

namespace App\Services\MailerService;

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\MailDeliveryRequest;
use App\Models\MailRecipient;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SurveyConfirmationService
{
    public function sendForResponse(SurveyResponse $response, ?string $fullName, string $email): MailDeliveryRequest
    {
        $normalizedName = $this->normalizeName($fullName);
        $normalizedEmail = $this->normalizeEmail($email);

        [$recipient, $deliveryRequest] = DB::transaction(function () use ($response, $normalizedName, $normalizedEmail) {
            $existingRecipient = MailRecipient::where('survey_response_id', $response->id)->first();

            $recipient = MailRecipient::updateOrCreate(
                ['survey_response_id' => $response->id],
                [
                    'survey_id' => $response->survey_id,
                    'pseudonym_uuid' => $existingRecipient?->pseudonym_uuid ?? (string) Str::uuid(),
                    'full_name_encrypted' => $normalizedName,
                    'email_encrypted' => $normalizedEmail,
                    'email_hash' => hash('sha256', $normalizedEmail),
                    'consent_source' => 'survey_submit',
                    'revoked_at' => null,
                ],
            );

            $deliveryRequest = $recipient->deliveryRequests()->create([
                'pseudonym_uuid' => $recipient->pseudonym_uuid,
                'survey_id' => $response->survey_id,
                'survey_response_id' => $response->id,
                'mail_template' => 'survey_submission_confirmation',
                'mail_status' => 'pending',
                'provider' => config('survey-mailing.provider'),
                'mail_requested_at' => now(),
            ]);

            return [$recipient, $deliveryRequest];
        });

        try {
            Mail::mailer(config('survey-mailing.mailer'))
                ->to($normalizedEmail)
                ->send(new SurveySubmissionConfirmationMail($response->fresh('survey'), $recipient->fresh()));

            $deliveryRequest->forceFill([
                'mail_status' => 'sent',
                'mail_sent_at' => now(),
                'mail_failed_at' => null,
                'failure_reason' => null,
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('Survey confirmation email could not be sent.', [
                'survey_response_id' => $response->id,
                'pseudonym_uuid' => $recipient->pseudonym_uuid,
                'provider' => config('survey-mailing.provider'),
                'message' => $exception->getMessage(),
            ]);

            $deliveryRequest->forceFill([
                'mail_status' => 'failed',
                'mail_failed_at' => now(),
                'failure_reason' => Str::limit($exception->getMessage(), 1000),
            ])->save();
        }

        return $deliveryRequest->fresh();
    }

    public function revokeForResponse(SurveyResponse $response): void
    {
        $recipient = MailRecipient::where('survey_response_id', $response->id)->first();

        if (! $recipient) {
            return;
        }

        $recipient->forceFill([
            'full_name_encrypted' => null,
            'email_encrypted' => null,
            'email_hash' => null,
            'revoked_at' => now(),
        ])->save();
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function normalizeName(?string $fullName): ?string
    {
        if (! filled($fullName)) {
            return null;
        }

        return trim($fullName);
    }
}
