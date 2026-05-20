<?php

namespace App\Actions\Surveys;

use App\Models\SurveyResponse;

class DeleteSurveySubmission
{
    public function handle(SurveyResponse $response): void
    {
        $response->contactInformationSubmission()->delete();
        $response->answers()->delete();
        $response->participantPointsHistories()->delete();
        $response->delete();
    }
}
