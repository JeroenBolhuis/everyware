<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Livewire;

it('forbids non-admins from the admin users area', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('admin.users.index'))->assertForbidden();
});

it('lets admins list users', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get(route('admin.users.index'))->assertOk();
});

it('lets admins create users with a role', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::User->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index', absolute: false));

    $created = User::query()->where('email', 'jane@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->hasRole(Role::User->value))->toBeTrue();
});

it('forbids non-admins from creating users via livewire', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.users.create')
        ->assertForbidden();
});

it('lets admins update users', function () {
    $admin = User::factory()->admin()->create();
    $subject = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $subject])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($subject->fresh()->name)->toBe('Updated Name');
});

it('prevents demoting the last administrator', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $admin])
        ->set('role', Role::User->value)
        ->call('save')
        ->assertHasErrors('role');
});
