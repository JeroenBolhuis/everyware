<?php

namespace App\Policies;

use App\Models\SurveyResponse;
use App\Models\User;

class SurveyResponsePolicy
{
    public function view(User $user, SurveyResponse $surveyResponse): bool
    {
        return $user->canReviewSurveyResponses();
    }

    public function delete(User $user, SurveyResponse $surveyResponse): bool
    {
        return $this->view($user, $surveyResponse);
    }
}
