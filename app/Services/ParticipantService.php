<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ParticipantIdentity;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single entry point for all operations that touch participant PII (email).
 *
 * The feedback database (`participants` table) never stores email addresses.
 * Email is only stored in the `personal` database (`participant_identities` table)
 * and retrieved on-demand through this service.
 *
 * In production the `personal` connection should use separate credentials so that
 * DBA access to the feedback database does not expose email addresses.
 */
class ParticipantService
{
    /**
     * Find an existing participant by email, or create both the feedback-DB record
     * and the personal-DB identity record atomically.
     */
    public function findOrCreateByEmail(string $email): Participant
    {
        $email = $this->normalizeEmail($email);

        $identity = ParticipantIdentity::where('email', $email)->first();

        if ($identity !== null) {
            return Participant::findOrFail($identity->participant_id);
        }

        return DB::transaction(function () use ($email): Participant {
            $participant = Participant::create([
                'academy' => null,
            ]);

            ParticipantIdentity::updateOrCreate(
                ['participant_id' => $participant->id],
                ['email' => $email],
            );

            return $participant;
        });
    }

    /**
     * Return the email address for trusted internal flows, or null when not found.
     *
     * UI and export code that displays personal data to employees must call
     * emailForAdmin() instead so authorization stays explicit at the call site.
     */
    public function emailForParticipant(int $participantId): ?string
    {
        return ParticipantIdentity::where('participant_id', $participantId)->value('email');
    }

    /**
     * Return the email address only when the requesting employee may view PII.
     *
     * @throws AuthorizationException
     */
    public function emailForAdmin(User $user, int $participantId): ?string
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Only admins may view participant personal data.');
        }

        return $this->emailForParticipant($participantId);
    }

    /**
     * Return the email address for internal message delivery without exposing it
     * to the employee-facing UI.
     *
     * @throws AuthorizationException
     */
    public function emailForParticipantMessage(User $user, int $participantId): ?string
    {
        if (! $user->canReviewSurveyResponses()) {
            throw new AuthorizationException('Only survey reviewers may message participants.');
        }

        return $this->emailForParticipant($participantId);
    }

    /**
     * Return unique participant email addresses for respondents of the given surveys.
     *
     * @param  array<int, int>  $surveyIds
     * @return array<int, string>
     *
     * @throws AuthorizationException
     */
    public function emailsForSurveyRespondents(User $user, array $surveyIds): array
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Only admins may use participant mailing lists.');
        }

        $surveyIds = collect($surveyIds)
            ->map(fn (mixed $surveyId): int => (int) $surveyId)
            ->filter(fn (int $surveyId): bool => $surveyId > 0)
            ->unique()
            ->values();

        if ($surveyIds->isEmpty()) {
            return [];
        }

        $participantIds = SurveyResponse::query()
            ->visibleInResults()
            ->whereIn('survey_id', $surveyIds)
            ->whereNotNull('participant_id')
            ->pluck('participant_id')
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return [];
        }

        return ParticipantIdentity::query()
            ->whereIn('participant_id', $participantIds)
            ->pluck('email')
            ->map(fn (string $email): string => $this->normalizeEmail($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Look up a participant purely by email address (used for auth and block checks).
     */
    public function findParticipantByEmail(string $email): ?Participant
    {
        $email = $this->normalizeEmail($email);

        $participantId = ParticipantIdentity::where('email', $email)->value('participant_id');

        return $participantId !== null ? Participant::find($participantId) : null;
    }

    /**
     * Determine whether a given email address belongs to a blocked participant.
     */
    public function isEmailBlocked(string $email): bool
    {
        $participant = $this->findParticipantByEmail($email);

        return $participant?->isBlocked() ?? false;
    }

    /**
     * Block a participant by email. Creates a participant record if none exists yet.
     */
    public function blockByEmail(string $email): Participant
    {
        $participant = $this->findOrCreateByEmail($email);
        $participant->block();

        return $participant;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
