<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\StoreSurveyContactDetailsRequest;
use App\Http\Requests\Surveys\StoreSurveyResponseRequest;
use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\Survey;
use App\Models\SurveyResponse;
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
            $query->where('title', 'like', '%' . $request->search . '%');
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
        $contactName = $this->normalizeContactValue($validated['contact_name'] ?? null);
        $contactEmail = $this->normalizeEmailForHash($validated['contact_email'] ?? null);

        $response = DB::transaction(function () use ($validated, $survey) {
            $response = $survey->responses()->create([
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

            $contactInformationPayload = $this->buildContactInformationPayload($validated, $response);

            if ($contactInformationPayload !== null) {
                $response->contactInformationSubmission()->updateOrCreate(
                    ['survey_response_id' => $response->id],
                    $contactInformationPayload,
                );
            }

            return $response;
        });

        $confirmationMailStatus = 'skipped';

        if ($contactEmail !== null) {
            try {
                Mail::to($contactEmail)->send(
                    new SurveySubmissionConfirmationMail($response->fresh('survey'), $contactName)
                );

                $confirmationMailStatus = 'sent';
            } catch (Throwable $exception) {
                Log::warning('Survey confirmation email could not be sent.', [
                    'survey_response_id' => $response->id,
                    'message' => $exception->getMessage(),
                ]);

                $confirmationMailStatus = 'failed';
            }
        }

        return to_route('survey.thankyou', $response)->with([
            'confirmationMailStatus' => $confirmationMailStatus,
        ]);
    }

    public function thankYou(SurveyResponse $response)
    {
        $response->loadMissing('contactInformationSubmission');

        return view('surveys.thankyou', compact('response'));
    }

    public function storeContactDetails(StoreSurveyContactDetailsRequest $request, SurveyResponse $response)
    {
        $validated = $request->validated();

        $contactInformationPayload = $this->buildContactInformationPayload($validated, $response);

        if ($contactInformationPayload === null) {
            return to_route('survey.thankyou', $response)
                ->with('contactDetailsSkipped', true);
        }

        $response->contactInformationSubmission()->updateOrCreate(
            ['survey_response_id' => $response->id],
            $contactInformationPayload,
        );

        return to_route('survey.thankyou', $response)
            ->with('contactDetailsSaved', true);
    }

    private function buildContactInformationPayload(array $validated, SurveyResponse $response): ?array
    {
        $contactName = $this->normalizeContactValue($validated['contact_name'] ?? null);
        $contactEmail = $this->normalizeEmailForHash($validated['contact_email'] ?? null);
        $contactPhone = $this->normalizePhoneForHash($validated['contact_phone'] ?? null);

        if (! filled($contactName) && ! filled($contactEmail) && ! filled($contactPhone)) {
            return null;
        }

        return [
            'survey_id' => $response->survey_id,
            'survey_response_id' => $response->id,
            'name' => $contactName,
            'email' => $contactEmail,
            'phone' => $contactPhone,
        ];
    }

    private function normalizeContactValue(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return trim($value);
    }

    private function normalizeEmailForHash(?string $value): ?string
    {
        $normalizedValue = $this->normalizeContactValue($value);

        return $normalizedValue !== null ? Str::lower($normalizedValue) : null;
    }

    private function normalizePhoneForHash(?string $value): ?string
    {
        $normalizedValue = $this->normalizeContactValue($value);

        if ($normalizedValue === null) {
            return null;
        }

        $hasLeadingPlus = str_starts_with($normalizedValue, '+');
        $digitsOnly = preg_replace('/\D+/', '', $normalizedValue) ?? '';

        if ($digitsOnly === '') {
            return null;
        }

        return $hasLeadingPlus ? '+' . $digitsOnly : $digitsOnly;
    }
}
