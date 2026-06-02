<?php

use App\Actions\Surveys\SurveyRetentionSettings;
use App\Models\SurveySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reads retention settings from config when no database setting exists', function () {
    config([
        'surveys.retention_years' => 6,
        'surveys.upcoming_warning_days' => 14,
    ]);

    $settings = new SurveyRetentionSettings;

    expect($settings->retentionYears())->toBe(6)
        ->and($settings->upcomingWarningDays())->toBe(14);
});

it('reads retention settings from the database when available', function () {
    SurveySetting::create([
        'id' => 1,
        'retention_years' => 4,
        'upcoming_warning_days' => 21,
    ]);

    $settings = new SurveyRetentionSettings;

    expect($settings->retentionYears())->toBe(4)
        ->and($settings->upcomingWarningDays())->toBe(21);
});

it('updates retention years while preserving warning days', function () {
    config(['surveys.upcoming_warning_days' => 9]);

    $settings = new SurveyRetentionSettings;
    $settings->updateRetentionYears(8);

    expect(SurveySetting::query()->first())
        ->retention_years->toBe(8)
        ->upcoming_warning_days->toBe(9)
        ->and(config('surveys.retention_years'))->toBe(8);
});
