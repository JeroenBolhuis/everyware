<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;

class SurveyWithdrawalController extends Controller
{
    public function show(string $token)
    {
        $response = SurveyResponse::where('withdrawal_token', $token)->firstOrFail();

        return view('surveys.withdraw', compact('response'));
    }

    public function destroy(string $token)
    {
        $response = SurveyResponse::where('withdrawal_token', $token)->firstOrFail();

        DB::transaction(function () use ($response) {
            $response->contactInformationSubmission()->delete();

            $response->forceFill([
                'student_name' => null,
                'student_email' => null,
                'withdrawn_at' => $response->withdrawn_at ?? now(),
            ])->save();
        });

        return view('surveys.withdraw-confirmed');
    }
}
