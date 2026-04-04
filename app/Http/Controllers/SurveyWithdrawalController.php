<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Services\MailerService\SurveyConfirmationService;
use Illuminate\Support\Facades\DB;

class SurveyWithdrawalController extends Controller
{
    public function show(string $token)
    {
        $response = SurveyResponse::where('withdrawal_token', $token)->firstOrFail();

        return view('surveys.withdraw', compact('response'));
    }

    public function destroy(string $token, SurveyConfirmationService $surveyConfirmationService)
    {
        $response = SurveyResponse::where('withdrawal_token', $token)->firstOrFail();

        DB::transaction(function () use ($response, $surveyConfirmationService) {
            $response->contactInformationSubmission()->delete();
            $surveyConfirmationService->revokeForResponse($response);

            if (! $response->withdrawn_at) {
                $response->update([
                    'withdrawn_at' => now(),
                ]);
            }
        });

        return view('surveys.withdraw-confirmed');
    }
}
