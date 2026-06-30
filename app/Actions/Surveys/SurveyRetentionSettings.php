<?php

namespace App\Actions\Surveys;

use App\Models\SurveySetting;
use Throwable;

class SurveyRetentionSettings
{
    public function retentionYears(): int
    {
        try {
            return (int) (SurveySetting::query()->value('retention_years') ?? config('surveys.retention_years'));
        } catch (Throwable) {
            return (int) config('surveys.retention_years');
        }
    }

    public function upcomingWarningDays(): int
    {
        try {
            return (int) (SurveySetting::query()->value('upcoming_warning_days') ?? config('surveys.upcoming_warning_days'));
        } catch (Throwable) {
            return (int) config('surveys.upcoming_warning_days');
        }
    }

    public function updateRetentionYears(int $retentionYears): void
    {
        SurveySetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'retention_years' => $retentionYears,
                'upcoming_warning_days' => $this->upcomingWarningDays(),
            ],
        );

        config(['surveys.retention_years' => $retentionYears]);
    }
}
