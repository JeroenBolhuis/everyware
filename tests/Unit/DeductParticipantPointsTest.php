<?php

use App\Actions\Participants\DeductParticipantPoints;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('deducts points and records the history entry', function () {
    $participant = Participant::create([
        'email' => 'jamie@example.com',
    ]);

    $participant->forceFill(['current_points' => 15])->save();

    $history = (new DeductParticipantPoints)(
        participant: $participant,
        points: 7,
        reason: 'Cadeaubon ingeleverd',
    );

    expect($participant->fresh()->current_points)->toBe(8)
        ->and($history->amount)->toBe(-7)
        ->and($history->reason)->toBe('Cadeaubon ingeleverd')
        ->and($history->source_type)->toBeNull()
        ->and($history->source_id)->toBeNull();
});

it('does not allow a participant balance to become negative', function () {
    $participant = Participant::create([
        'email' => 'jamie@example.com',
    ]);

    $participant->forceFill(['current_points' => 3])->save();

    (new DeductParticipantPoints)(
        participant: $participant,
        points: 4,
        reason: 'Te veel punten',
    );
})->throws(InvalidArgumentException::class, 'De deelnemer heeft niet genoeg punten.');

it('requires a positive amount of points', function () {
    $participant = new Participant([
        'email' => 'jamie@example.com',
    ]);

    (new DeductParticipantPoints)($participant, 0, 'Ongeldig');
})->throws(InvalidArgumentException::class, 'Het aantal punten moet minimaal 1 zijn.');

it('requires a reason', function () {
    $participant = new Participant([
        'email' => 'jamie@example.com',
    ]);

    (new DeductParticipantPoints)($participant, 5, '   ');
})->throws(InvalidArgumentException::class, 'Geef een reden op voor de puntenaftrek.');
