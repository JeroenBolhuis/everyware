<?php

namespace App\Http\Controllers;

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
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
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by title
        if ($request->has('search') && $request->search !== '') {
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

    public function store(Request $request, Survey $survey)
    {
        $survey->load('questions');

        $rules = [
            'answers' => ['required', 'array'],
        ];

        foreach ($survey->questions as $question) {
            $rules["answers.{$question->id}"] = $question->required ? ['required'] : ['nullable'];
        }

        $validated = $request->validate($rules);

        $response = DB::transaction(function () use ($validated, $survey) {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'withdrawal_token' => Str::uuid(),
                'submitted_at' => now(),
            ]);

            foreach ($validated['answers'] as $questionId => $answer) {
                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $questionId,
                    'answer' => $answer,
                ]);
            }

            return $response;
        });

        return redirect()->route('survey.thankyou', $response);
    }

    public function thankYou(SurveyResponse $response)
    {
        $response->loadMissing('contactInformationSubmission');

        return view('surveys.thankyou', compact('response'));
    }

    public function storeContactDetails(Request $request, SurveyResponse $response)
    {
        $validated = $request->validate([
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ], [
            'contact_email.email' => 'Vul een geldig e-mailadres in.',
            'contact_phone.regex' => 'Vul een geldig telefoonnummer in.',
        ]);

        $contactInformationPayload = $this->buildContactInformationPayload($validated, $response);

        if ($contactInformationPayload === null) {
            return redirect()
                ->route('survey.thankyou', $response)
                ->with('contactDetailsSkipped', true);
        }

        ContactInformationSubmission::updateOrCreate(
            ['survey_response_id' => $response->id],
            $contactInformationPayload,
        );

        return redirect()
            ->route('survey.thankyou', $response)
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
