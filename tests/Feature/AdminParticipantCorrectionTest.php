<?php

use App\Models\Participant;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('lets admins apply negative point corrections', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::create([
        'name' => 'Jamie Jansen',
        'email' => 'jamie@example.com',
    ]);

    $participant->forceFill([
        'current_points' => 3,
    ])->save();

    actingAs($admin);

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('amount', -5)
        ->set('reason', 'Handmatige correctie')
        ->call('addCorrection')
        ->assertHasNoErrors();

    expect($participant->fresh()->current_points)->toBe(-2);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => -5,
        'reason' => 'Handmatige correctie',
        'source_type' => null,
        'source_id' => null,
    ]);
});
