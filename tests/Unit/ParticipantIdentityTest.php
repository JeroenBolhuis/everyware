<?php

use App\Models\Participant;
use App\Models\ParticipantIdentity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores participant identities on the personal database connection', function () {
    $identity = new ParticipantIdentity([
        'participant_id' => 123,
        'email' => 'student@example.com',
    ]);

    expect($identity->getConnectionName())->toBe('personal')
        ->and($identity->participant_id)->toBe(123)
        ->and($identity->email)->toBe('student@example.com');
});

it('defines a participant relationship without storing email on the participant model', function () {
    $relation = (new ParticipantIdentity)->participant();

    expect($relation)->toBeInstanceOf(BelongsTo::class)
        ->and($relation->getRelated())->toBeInstanceOf(Participant::class);
});
