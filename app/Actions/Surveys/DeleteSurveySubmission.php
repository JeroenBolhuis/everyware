<?php

namespace App\Actions\Surveys;

use App\Models\SurveyResponse;

class DeleteSurveySubmission
{
    public function handle(SurveyResponse $response): void
    {
        $response->participantPointsHistories()->delete();
        $response->delete();
    }
}
