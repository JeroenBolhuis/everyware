<?php

namespace App\Http\Controllers;

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Http\Requests\Surveys\StoreSurveyResponseRequest;
use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyAnswerRetentionSetting;
use App\Models\SurveyResponse;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::query();

        $status = $request->input('status');
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $surveys = $query->paginate(10);

        return view('surveys.index', compact('surveys'));
    }

    public function show(Survey $survey)
    {
        $survey->load('questions');

        abort_unless($survey->is_active, 404);

        return view('surveys.show', compact('survey'));
    }

    public function showByToken(string $token)
    {
        $survey = Survey::where('share_token', $token)->firstOrFail();
        $survey->load('questions');

        abort_unless($survey->is_active, 404);

        return view('surveys.show', compact('survey'));
    }

    public function storeByToken(StoreSurveyResponseRequest $request, string $token)
    {
        $survey = Survey::where('share_token', $token)->firstOrFail();

        abort_unless($survey->is_active, 404);

        return $this->store($request, $survey);
    }

    public function store(StoreSurveyResponseRequest $request, Survey $survey)
    {
        $validated = $request->validated();
        /** @var Participant $participant */
        $participant = $request->user('participant');

        if ($participant->isBlocked()) {
            return to_route('survey.thankyou.generic');
        }

        $response = DB::transaction(function () use ($validated, $survey, $participant) {
            $submittedAt = now();

            $response = $survey->responses()->create([
                'participant_id' => $participant->id,
                'is_anonymous' => true,
                'withdrawal_token' => Str::uuid(),
                'submitted_at' => $submittedAt,
                'delete_on_date' => $this->resolveResponseDeleteOnDate($submittedAt),
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

        return to_route('survey.thankyou', $response);
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

    private function resolveResponseDeleteOnDate(?CarbonInterface $submittedAt): ?string
    {
        $autoDeleteAfterDays = SurveyAnswerRetentionSetting::query()->value('auto_delete_after_days');

        if ($autoDeleteAfterDays === null) {
            return null;
        }

        return $submittedAt?->copy()->addDays($autoDeleteAfterDays)->toDateString()
            ?? now()->addDays($autoDeleteAfterDays)->toDateString();
    }
}
