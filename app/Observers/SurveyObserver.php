<?php

namespace App\Observers;

use App\Models\Survey;
use Illuminate\Support\Str;

class SurveyObserver
{
    public function creating(Survey $survey): void
    {
        if (empty($survey->share_token)) {
            $survey->share_token = (string) Str::uuid();
        }
    }
}
