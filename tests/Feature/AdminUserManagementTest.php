<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('forbids non-admins from the admin users area', function () {
    $user = User::factory()->createOne();

    /** @var User $user */
    actingAs($user);

    get(route('admin.users.index'))->assertForbidden();
});

it('lets admins list users', function () {
    $admin = User::factory()->admin()->createOne();
    actingAs($admin);

    get(route('admin.users.index'))->assertOk();
});

it('lets admins create users with a single role', function () {
    $admin = User::factory()->admin()->createOne();
    actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::LicEmployee->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index', absolute: false));

    $created = User::query()->where('email', 'jane@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->getRoleNames()->all())->toBe([Role::LicEmployee->value]);
});

it('lets admins select one role with the role cards', function () {
    $admin = User::factory()->admin()->createOne();
    actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->assertSet('role', Role::LicEmployee->value)
        ->set('role', Role::Admin->value)
        ->assertSet('role', Role::Admin->value)
        ->assertSee('type="radio"', false)
        ->assertSee('Enquêtes aanmaken, bewerken en sluiten')
        ->assertSee('Rollen toewijzen');
});

it('forbids non-admins from creating users via livewire', function () {
    $user = User::factory()->createOne();

    /** @var User $user */
    actingAs($user);

    Livewire::test('pages::admin.users.create')
        ->assertForbidden();
});

it('lets admins update users', function () {
    $admin = User::factory()->admin()->createOne();
    $subject = User::factory()->createOne();
    actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $subject])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($subject->fresh()->name)->toBe('Updated Name');
});

it('replaces the existing role with one selected role when saving', function () {
    $admin = User::factory()->admin()->createOne();
    $subject = User::factory()->licEmployee()->createOne();
    actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $subject])
        ->assertSet('role', Role::LicEmployee->value)
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($subject->fresh()->getRoleNames()->all())->toBe([Role::Admin->value]);
});

it('prevents demoting the last administrator', function () {
    $admin = User::factory()->admin()->createOne();
    actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $admin])
        ->set('role', Role::User->value)
        ->call('save')
        ->assertHasErrors('role');
});
