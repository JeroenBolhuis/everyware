<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;

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

        if (! $response->withdrawn_at) {
            $response->update([
                'withdrawn_at' => now(),
            ]);
        }

        return view('surveys.withdraw-confirmed');
    }
}
