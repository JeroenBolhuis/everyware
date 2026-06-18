<?php

use App\Models\Participant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('lets admins deduct participant points', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    $participant->forceFill([
        'current_points' => 8,
    ])->save();

    actingAs($admin);

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'jamie@example.com')
        ->call('findParticipantByEmail')
        ->assertSet('emailParticipantId', $participant->id)
        ->assertDontSee($participant->public_code)
        ->set('mutationType', 'deduct')
        ->set('pointsAmount', 5)
        ->set('reason', 'Handmatige correctie')
        ->call('adjustEmailParticipantPoints')
        ->assertHasNoErrors();

    expect($participant->fresh()->current_points)->toBe(3);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => -5,
        'reason' => 'Handmatige correctie',
        'source_type' => null,
        'source_id' => null,
    ]);
});
