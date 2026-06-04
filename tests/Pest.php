<?php

use App\Models\Participant;
use App\Services\ParticipantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function loginParticipantAs(Participant $participant): void
{
    test()->actingAs($participant, 'participant');
}

/**
 * Find a participant by email via the personal DB (ParticipantService).
 * Use this instead of Participant::where('email', ...) in tests.
 */
function participantByEmail(string $email): ?Participant
{
    return app(ParticipantService::class)->findParticipantByEmail($email);
}
