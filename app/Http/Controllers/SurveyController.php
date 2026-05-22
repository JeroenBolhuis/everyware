<?php

namespace App\Http\Controllers;

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Http\Requests\Surveys\StoreSurveyResponseRequest;
use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::query()
            ->withCount('questions')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', today());
            });

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $surveys = $query->latest()->paginate(10);
        $completedSurveyIds = $this->completedSurveyIdsForCurrentParticipant();

        return view('surveys.index', compact('completedSurveyIds', 'surveys'));
    }

    public function show(Survey $survey)
    {
        $survey->load('questions');

        abort_unless($survey->is_active, 404);

        if ($survey->hasEnded()) {
            return $this->expiredSurveyResponse($survey);
        }

        $existingResponse = $this->existingResponseForCurrentParticipant($survey);

        if ($existingResponse !== null) {
            return view('surveys.already-completed', [
                'survey' => $survey,
                'response' => $existingResponse,
            ]);
        }

        return view('surveys.show', compact('survey'));
    }

    public function showByToken(string $token)
    {
        $survey = Survey::where('share_token', $token)->firstOrFail();
        $survey->load('questions');

        abort_unless($survey->is_active, 404);

        if ($survey->hasEnded()) {
            return $this->expiredSurveyResponse($survey);
        }

        $existingResponse = $this->existingResponseForCurrentParticipant($survey);

        if ($existingResponse !== null) {
            return view('surveys.already-completed', [
                'survey' => $survey,
                'response' => $existingResponse,
            ]);
        }

        return view('surveys.show', compact('survey'));
    }

    public function storeByToken(StoreSurveyResponseRequest $request, string $token)
    {
        $survey = Survey::where('share_token', $token)->firstOrFail();

        abort_unless($survey->is_active, 404);

        if ($survey->hasEnded()) {
            return $this->expiredSurveyResponse($survey);
        }

        return $this->store($request, $survey);
    }

    public function store(StoreSurveyResponseRequest $request, Survey $survey)
    {
        $validated = $request->validated();
        /** @var Participant $participant */
        $participant = $request->user('participant');

        if ($survey->hasEnded()) {
            return $this->expiredSurveyResponse($survey);
        }

        if ($participant->isBlocked() || $this->isBlockedEmail($participant->email)) {
            return to_route('survey.thankyou.generic');
        }

        $response = DB::transaction(function () use ($validated, $survey, $participant) {
            Participant::query()
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingResponse = $this->existingResponseForParticipant($survey, $participant);

            if ($existingResponse !== null) {
                return $existingResponse;
            }

            $response = $survey->responses()->create([
                'participant_id' => $participant->id,
                'is_anonymous' => true,
                'withdrawal_token' => Str::uuid(),
                'submitted_at' => now(),
            ]);

            $answers = collect($validated['answers'])
                ->map(fn ($answer, $questionId) => [
                    'survey_question_id' => $questionId,
                    'answer' => $answer,
                ])
                ->values()
                ->all();

            $response->answers()->createMany($answers);

            return $response;
        });

        if ($response->wasRecentlyCreated === false) {
            return to_route('survey.already-completed', $response);
        }

        return to_route('survey.thankyou', $response);
    }

    public function alreadyCompleted(SurveyResponse $response)
    {
        /** @var Participant $participant */
        $participant = request()->user('participant');

        abort_unless($response->participant_id === $participant->id, 403);

        $response->loadMissing('survey', 'participantPointsHistories');

        return view('surveys.already-completed', [
            'survey' => $response->survey,
            'response' => $response,
        ]);
    }

    public function thankYou(SurveyResponse $response)
    {
        $response->loadMissing('contactInformationSubmission', 'participant', 'participantPointsHistories', 'survey');

        return view('surveys.thankyou', compact('response'));
    }

    public function genericThankYou()
    {
        return view('surveys.thankyou', ['response' => null]);
    }

    public function allowContact(SurveyResponse $response, DeleteSurveySubmission $deleteSurveySubmission)
    {
        /** @var Participant $participant */
        $participant = request()->user('participant');

        abort_unless($response->participant_id === $participant->id, 403);

        if ($participant->isBlocked() || $this->isBlockedEmail($participant->email)) {
            DB::transaction(function () use ($deleteSurveySubmission, $response): void {
                $deleteSurveySubmission->handle($response);
            });

            return to_route('survey.thankyou.generic');
        }

        DB::transaction(function () use ($response, $participant): void {
            $response->forceFill(['is_anonymous' => false])->save();
            $this->awardPointsForResponse($response, $participant);
        });

        $confirmationMailStatus = $this->sendConfirmationMail($response, $participant->email);

        return to_route('survey.thankyou', $response)
            ->with([
                'contactAllowed' => true,
                'confirmationMailStatus' => $confirmationMailStatus,
            ]);
    }

    private function awardPointsForResponse(SurveyResponse $response, Participant $participant): void
    {
        $response->loadMissing('survey', 'participantPointsHistories');

        if ($response->participantPointsHistories->isNotEmpty()) {
            return;
        }

        $points = (int) ($response->survey?->reward_points ?? 0);

        if ($points <= 0) {
            return;
        }

        ParticipantPointsHistory::create([
            'participant_id' => $participant->id,
            'amount' => $points,
            'source_type' => $response::class,
            'source_id' => $response->id,
        ]);

        $participant->increment('current_points', $points);
        $participant->refresh();

        $response->unsetRelation('participantPointsHistories');
        $response->setRelation('participant', $participant);
    }

    private function sendConfirmationMail(SurveyResponse $response, ?string $contactEmail): string
    {
        if ($contactEmail === null) {
            return 'skipped';
        }

        try {
            Mail::to($contactEmail)->send(
                new SurveySubmissionConfirmationMail(
                    $response->fresh(['survey', 'participant', 'participantPointsHistories']),
                    null
                )
            );

            return 'sent';
        } catch (Throwable $exception) {
            Log::warning('Survey confirmation email could not be sent.', [
                'survey_response_id' => $response->id,
                'message' => $exception->getMessage(),
            ]);

            return 'failed';
        }
    }

    private function isBlockedEmail(?string $contactEmail): bool
    {
        if ($contactEmail === null) {
            return false;
        }

        return Participant::query()
            ->where('email', $contactEmail)
            ->whereNotNull('blocked_at')
            ->exists();
    }

    private function existingResponseForCurrentParticipant(Survey $survey): ?SurveyResponse
    {
        /** @var Participant|null $participant */
        $participant = request()->user('participant');

        if ($participant === null) {
            return null;
        }

        return $this->existingResponseForParticipant($survey, $participant);
    }

    private function existingResponseForParticipant(Survey $survey, Participant $participant): ?SurveyResponse
    {
        return SurveyResponse::query()
            ->whereBelongsTo($survey)
            ->whereBelongsTo($participant)
            ->latest('submitted_at')
            ->first();
    }

    /**
     * @return array<int, int>
     */
    private function completedSurveyIdsForCurrentParticipant(): array
    {
        /** @var Participant|null $participant */
        $participant = request()->user('participant');

        if ($participant === null) {
            return [];
        }

        return SurveyResponse::query()
            ->whereBelongsTo($participant)
            ->pluck('survey_id')
            ->map(fn (int|string $surveyId): int => (int) $surveyId)
            ->all();
    }

    private function expiredSurveyResponse(Survey $survey): Response
    {
        return response()->view('surveys.expired', compact('survey'), 410);
    }
}
