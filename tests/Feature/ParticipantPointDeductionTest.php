<?php

use App\Models\Participant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

it('lets admins deduct points from a participant', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    $participant->forceFill(['current_points' => 12])->save();

    actingAs($admin);

    get(route('admin.participants.index'))
        ->assertSuccessful()
        ->assertDontSee('jamie@example.com')
        ->assertDontSee('Punten aanpassen via e-mail')
        ->assertSee('Overzicht');

    get(route('admin.participants.points'))
        ->assertSuccessful()
        ->assertDontSee($participant->public_code)
        ->assertSee('Punten aanpassen');

    get(route('admin.participants.show', $participant))
        ->assertSuccessful()
        ->assertDontSee('jamie@example.com')
        ->assertSee($participant->public_code);

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'jamie@example.com')
        ->call('findParticipantByEmail')
        ->assertSet('emailParticipantId', $participant->id)
        ->assertDontSee($participant->public_code)
        ->set('mutationType', 'deduct')
        ->set('pointsAmount', 5)
        ->set('reason', 'Extern beloning ingeleverd')
        ->call('adjustEmailParticipantPoints')
        ->assertHasNoErrors();

    expect($participant->fresh()->current_points)->toBe(7);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => -5,
        'reason' => 'Extern beloning ingeleverd',
        'source_type' => null,
        'source_id' => null,
    ]);
});

it('lets lic employees view participants and deduct points', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('sam@example.com')->createOne();

    $participant->forceFill(['current_points' => 10])->save();

    actingAs($employee);

    get(route('admin.participants.index'))
        ->assertSuccessful()
        ->assertSee($participant->public_code)
        ->assertDontSee('sam@example.com');

    get(route('admin.participants.show', $participant))
        ->assertSuccessful()
        ->assertSee($participant->public_code)
        ->assertDontSee('sam@example.com');

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'sam@example.com')
        ->call('findParticipantByEmail')
        ->assertSet('emailParticipantId', $participant->id)
        ->assertDontSee($participant->public_code)
        ->set('mutationType', 'deduct')
        ->set('pointsAmount', 4)
        ->set('reason', 'Lunchbon ingeleverd')
        ->call('adjustEmailParticipantPoints')
        ->assertHasNoErrors();

    expect($participant->fresh()->current_points)->toBe(6);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => -4,
        'reason' => 'Lunchbon ingeleverd',
    ]);
});

it('keeps regular users from deducting participant points', function () {
    $user = User::factory()->createOne();
    $participant = Participant::factory()->withEmail('sam@example.com')->createOne();

    actingAs($user);

    get(route('admin.participants.index'))->assertForbidden();
    get(route('admin.participants.points'))->assertForbidden();

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->assertForbidden();
});

it('validates the deduction form', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    $participant->forceFill(['current_points' => 12])->save();

    actingAs($admin);

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'jamie@example.com')
        ->call('findParticipantByEmail')
        ->set('pointsAmount', 0)
        ->set('reason', '')
        ->call('adjustEmailParticipantPoints')
        ->assertHasErrors([
            'pointsAmount' => 'min',
            'reason' => 'required',
        ]);

    expect($participant->fresh()->current_points)->toBe(12);
});
it('does not let admins deduct more points than the participant has', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();

    $participant->forceFill(['current_points' => 3])->save();

    actingAs($admin);

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'jamie@example.com')
        ->call('findParticipantByEmail')
        ->set('mutationType', 'deduct')
        ->set('pointsAmount', 4)
        ->set('reason', 'Te veel punten')
        ->call('adjustEmailParticipantPoints')
        ->assertHasErrors(['pointsAmount']);

    expect($participant->fresh()->current_points)->toBe(3);
});

it('lets employees add points from an email lookup without showing the public code', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('sam@example.com')->createOne();

    $participant->forceFill(['current_points' => 2])->save();

    actingAs($employee);

    Livewire::test('pages::admin.participants.points')
        ->set('emailSearch', 'sam@example.com')
        ->call('findParticipantByEmail')
        ->assertSet('emailParticipantId', $participant->id)
        ->assertSee('Huidige punten')
        ->assertDontSee($participant->public_code)
        ->set('mutationType', 'add')
        ->set('pointsAmount', 3)
        ->set('reason', 'Correctie')
        ->call('adjustEmailParticipantPoints')
        ->assertHasNoErrors();

    expect($participant->fresh()->current_points)->toBe(5);

    assertDatabaseHas('participant_points_history', [
        'participant_id' => $participant->id,
        'amount' => 3,
        'reason' => 'Correctie',
    ]);
});
