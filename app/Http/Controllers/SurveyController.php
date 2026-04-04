<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\StoreSurveyContactDetailsRequest;
use App\Http\Requests\Surveys\StoreSurveyResponseRequest;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\MailerService\SurveyConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::query();

        // Filter by status
            $status = $request->input('status');
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('is_active', $status === 'active');
            }

        // Search by title
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

    public function store(StoreSurveyResponseRequest $request, Survey $survey, SurveyConfirmationService $surveyConfirmationService)
    {
        $validated = $request->validated();
        $contactName = $this->normalizeContactValue($validated['contact_name'] ?? null);
        $contactEmail = $this->normalizeEmailForHash($validated['contact_email'] ?? null);

        $response = DB::transaction(function () use ($validated, $survey) {
            $response = $survey->responses()->create([
                'student_name' => $this->normalizeContactValue($validated['contact_name'] ?? null),
                'student_email' => $this->normalizeEmailForHash($validated['contact_email'] ?? null),
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

        if ($contactEmail !== null) {
            $deliveryRequest = $surveyConfirmationService->sendForResponse($response, $contactName, $contactEmail);

            return to_route('survey.thankyou', $response)->with([
                'confirmationMailStatus' => $deliveryRequest->mail_status,
            ]);
        }

        return to_route('survey.thankyou', $response)->with([
            'confirmationMailStatus' => 'skipped',
        ]);
    }

    public function thankYou(SurveyResponse $response)
    {
        $response->loadMissing('contactInformationSubmission', 'mailDeliveryRequests');

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
            'name' => $this->hashContactValue($contactName),
            'email' => $this->hashContactValue($contactEmail),
            'phone' => $this->hashContactValue($contactPhone),
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

    private function hashContactValue(?string $value): ?string
    {
        return filled($value) ? Hash::make($value) : null;
    }
}
