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
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    $participant->block();
    $firstBlockedAt = $participant->fresh()->blocked_at;

    expect($firstBlockedAt)->not->toBeNull();

    $participant->fresh()->block();

    expect($participant->fresh()->blocked_at?->toDateTimeString())
        ->toBe($firstBlockedAt?->toDateTimeString());
});

it('uses a pseudonym as participant display name', function () {
    $admin = User::factory()->admin()->createOne();
    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    expect($participant->public_code)->toHaveLength(8)
        ->and($participant->displayNameFor($admin))->toBe($participant->public_code)
        ->and($participant->displayEmailFor($admin))->toBe('Afgeschermd')
        ->and($participant->displayNameFor($employee))->toBe($participant->public_code)
        ->and($participant->displayEmailFor($employee))->toBe('Afgeschermd');
});
