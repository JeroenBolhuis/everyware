<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
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
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'answers' => ['required', 'array'],
        ];

        foreach ($survey->questions as $question) {
            if ($question->required) {
                $rules["answers.{$question->id}"] = ['required'];
            } else {
                $rules["answers.{$question->id}"] = ['nullable'];
            }
        }

        $validated = $request->validate($rules);

        $response = DB::transaction(function () use ($validated, $survey) {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'student_name' => $validated['student_name'] ?? null,
                'student_email' => $validated['student_email'] ?? null,
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

        return redirect()->route('surveys.thankyou', $response);
    }

    public function thankYou(SurveyResponse $response)
    {
        return view('surveys.thankyou', compact('response'));
    }
}
