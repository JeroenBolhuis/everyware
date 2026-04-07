<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('forbids non-admins from the admin users area', function () {
    $user = User::factory()->create();
    actingAs($user);

    get(route('admin.users.index'))->assertForbidden();
});

it('lets admins list users', function () {
    $admin = User::factory()->admin()->create();
    actingAs($admin);

    get(route('admin.users.index'))->assertOk();
});

it('lets admins create users with roles', function () {
    $admin = User::factory()->admin()->create();
    actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('roles', [Role::User->value, Role::LICEmployee->value])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index', absolute: false));

    $created = User::query()->where('email', 'jane@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->hasRole(Role::User->value))->toBeTrue()
        ->and($created->hasRole(Role::LICEmployee->value))->toBeTrue();
});

it('forbids non-admins from creating users via livewire', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test('pages::admin.users.create')
        ->assertForbidden();
});

it('lets admins update users', function () {
    $admin = User::factory()->admin()->create();
    $subject = User::factory()->create();
    actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $subject])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($subject->fresh()->name)->toBe('Updated Name');
});

it('prevents demoting the last administrator', function () {
    $admin = User::factory()->admin()->create();
    actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $admin])
        ->set('roles', [Role::User->value])
        ->call('save')
        ->assertHasErrors('roles');
});
