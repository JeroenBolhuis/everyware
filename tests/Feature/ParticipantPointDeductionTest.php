<?php

use App\Models\Participant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

it('lets admins deduct points from a participant', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::create([
        'name' => 'Jamie Jansen',
        'email' => 'jamie@example.com',
    ]);

    $participant->forceFill(['current_points' => 12])->save();

    actingAs($admin);

    get(route('admin.participants.index'))
        ->assertSuccessful()
        ->assertSee('Jamie Jansen')
        ->assertSee('jamie@example.com');

    get(route('admin.participants.show', $participant))
        ->assertSuccessful()
        ->assertSee('Jamie Jansen')
        ->assertSee('jamie@example.com');

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('pointsToDeduct', 5)
        ->set('reason', 'Extern beloning ingeleverd')
        ->call('deductPoints')
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
    $participant = Participant::create([
        'name' => 'Sam Student',
        'email' => 'sam@example.com',
    ]);

    $participant->forceFill(['current_points' => 10])->save();

    actingAs($employee);

    get(route('admin.participants.index'))
        ->assertSuccessful()
        ->assertSee("#{$participant->id}")
        ->assertDontSee('Sam Student')
        ->assertDontSee('sam@example.com');

    get(route('admin.participants.show', $participant))
        ->assertSuccessful()
        ->assertSee("#{$participant->id}")
        ->assertDontSee('Sam Student')
        ->assertDontSee('sam@example.com');

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('pointsToDeduct', 4)
        ->set('reason', 'Lunchbon ingeleverd')
        ->call('deductPoints')
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
    $participant = Participant::create([
        'name' => 'Sam Student',
        'email' => 'sam@example.com',
    ]);

    actingAs($user);

    get(route('admin.participants.index'))->assertForbidden();

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->assertForbidden();
});

it('validates the deduction form', function () {
    $admin = User::factory()->admin()->createOne();
    $participant = Participant::create([
        'name' => 'Jamie Jansen',
        'email' => 'jamie@example.com',
    ]);

    $participant->forceFill(['current_points' => 12])->save();

    actingAs($admin);

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('pointsToDeduct', 0)
        ->set('reason', '')
        ->call('deductPoints')
        ->assertHasErrors([
            'pointsToDeduct' => 'min',
            'reason' => 'required',
        ]);

    expect($participant->fresh()->current_points)->toBe(12);
});
