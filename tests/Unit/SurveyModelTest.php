<?php

use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('assigns a share token automatically when creating a survey', function () {
    $survey = Survey::create([
        'title' => 'Nieuwe enquete',
        'description' => 'Beschrijving',
        'is_active' => true,
    ]);

    expect($survey->share_token)->not->toBeNull();
});

it('keeps a predefined share token intact', function () {
    $survey = Survey::create([
        'title' => 'Nieuwe enquete',
        'description' => 'Beschrijving',
        'is_active' => true,
        'share_token' => 'fixed-token',
    ]);

    expect($survey->share_token)->toBe('fixed-token');
});

it('uses the default reward points value and applies casts', function () {
    $survey = Survey::create([
        'title' => 'Beloningen',
        'description' => 'Beschrijving',
        'is_active' => 1,
        'ends_at' => today()->toDateString(),
    ]);

    expect($survey->reward_points)->toBe(10);
    expect($survey->is_active)->toBeTrue();
    expect($survey->ends_at->toDateString())->toBe(today()->toDateString());
});

it('knows whether it is accepting responses based on status and end date', function () {
    $activeWithoutEndDate = Survey::factory()->make([
        'is_active' => true,
        'ends_at' => null,
    ]);
    $activeEndingToday = Survey::factory()->make([
        'is_active' => true,
        'ends_at' => today(),
    ]);
    $expired = Survey::factory()->make([
        'is_active' => true,
        'ends_at' => today()->subDay(),
    ]);
    $closed = Survey::factory()->make([
        'is_active' => false,
        'ends_at' => null,
    ]);

    expect($activeWithoutEndDate->isAcceptingResponses())->toBeTrue()
        ->and($activeEndingToday->isAcceptingResponses())->toBeTrue()
        ->and($expired->hasEnded())->toBeTrue()
        ->and($expired->isAcceptingResponses())->toBeFalse()
        ->and($closed->isAcceptingResponses())->toBeFalse();
});

it('returns questions ordered by sort order', function () {
    $survey = Survey::create([
        'title' => 'Sortering',
        'description' => 'Beschrijving',
        'is_active' => true,
    ]);

    $survey->questions()->create([
        'question' => 'Tweede vraag',
        'type' => 'textarea',
        'required' => true,
        'sort_order' => 2,
    ]);

    $survey->questions()->create([
        'question' => 'Eerste vraag',
        'type' => 'textarea',
        'required' => true,
        'sort_order' => 1,
    ]);

    expect($survey->fresh()->questions->pluck('question')->all())
        ->toBe(['Eerste vraag', 'Tweede vraag']);
});
