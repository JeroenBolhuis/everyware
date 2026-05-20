<?php

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('knows whether a participant is blocked', function () {
    $participant = new Participant([
        'blocked_at' => now(),
    ]);

    expect($participant->isBlocked())->toBeTrue();
    expect((new Participant)->isBlocked())->toBeFalse();
});

it('blocks a participant once and persists the timestamp', function () {
    $participant = Participant::create([
        'email' => 'jamie@example.com',
    ]);

    $participant->block();
    $firstBlockedAt = $participant->fresh()->blocked_at;

    expect($firstBlockedAt)->not->toBeNull();

    $participant->fresh()->block();

    expect($participant->fresh()->blocked_at?->toDateTimeString())
        ->toBe($firstBlockedAt?->toDateTimeString());
});

it('shows real details only to admins', function () {
    $admin = User::factory()->admin()->createOne();
    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::create([
        'email' => 'jamie@example.com',
    ]);

    expect($participant->displayNameFor($admin))->toBe('—')
        ->and($participant->displayEmailFor($admin))->toBe('jamie@example.com')
        ->and($participant->displayNameFor($employee))->toBe("#{$participant->id}")
        ->and($participant->displayEmailFor($employee))->toBe('Afgeschermd');
});
