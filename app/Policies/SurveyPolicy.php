<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canReviewSurveyResponses();
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->canReviewSurveyResponses();
    }
}