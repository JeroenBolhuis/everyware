<?php

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('validates and creates a new user', function () {
    $user = (new CreateNewUser)->create([
        'name' => 'Jamie van Dijk',
        'email' => 'jamie@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Jamie van Dijk')
        ->and($user->email)->toBe('jamie@example.com')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('rejects invalid user registration input', function () {
    expect(fn () => (new CreateNewUser)->create([
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]))->toThrow(ValidationException::class);
});

it('validates and resets a user password', function () {
    $user = User::factory()->createOne([
        'password' => 'old-password',
    ]);

    (new ResetUserPassword)->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects invalid password reset input', function () {
    $user = User::factory()->createOne();

    expect(fn () => (new ResetUserPassword)->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'different',
    ]))->toThrow(ValidationException::class);
});
