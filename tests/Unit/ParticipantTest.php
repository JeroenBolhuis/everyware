<?php

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('knows whether a participant is blocked', function () {
    $participant = new Participant([
        'blocked_at' => now(),
    ]);

    expect($participant->isBlocked())->toBeTrue();
    expect((new Participant())->isBlocked())->toBeFalse();
});

it('blocks a participant once and persists the timestamp', function () {
    $participant = Participant::create([
        'email' => 'jamie@example.com',
        'name' => 'Jamie Jansen',
    ]);

    $participant->block();
    $firstBlockedAt = $participant->fresh()->blocked_at;

    expect($firstBlockedAt)->not->toBeNull();

    $participant->fresh()->block();

    expect($participant->fresh()->blocked_at?->toDateTimeString())
        ->toBe($firstBlockedAt?->toDateTimeString());
});
