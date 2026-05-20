<?php

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('detects whether contact details were shared', function () {
    $response = new SurveyResponse(['is_anonymous' => false]);

    expect($response->hasSharedContactDetails())->toBeTrue();

    $response->forceFill(['is_anonymous' => true]);

    expect($response->hasSharedContactDetails())->toBeFalse();
});

it('returns readable labels for the shared contact fields', function () {
    $response = new SurveyResponse(['is_anonymous' => false]);

    expect($response->sharedContactFieldLabels())->toBe([
        'E-mailadres zichtbaar voor LIC',
    ]);
});

it('calculates awarded and total points from related models', function () {
    $response = new SurveyResponse;

    $response->setRelation('participantPointsHistories', collect([
        new ParticipantPointsHistory(['amount' => 10]),
        new ParticipantPointsHistory(['amount' => -3]),
        new ParticipantPointsHistory(['amount' => 5]),
    ]));

    $participant = new Participant;
    $participant->forceFill([
        'current_points' => 27,
    ]);

    $response->setRelation('participant', $participant);

    expect($response->awardedPoints())->toBe(12);
    expect($response->totalPoints())->toBe(27);
});

it('falls back to zero points when no participant is linked', function () {
    $response = new SurveyResponse;
    $response->setRelation('participant', null);

    expect($response->totalPoints())->toBe(0);
});
