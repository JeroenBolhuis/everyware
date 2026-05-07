<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('redirects guests to the login page', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

it('redirects authenticated users to the public surveys overview from the dashboard route', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('surveys.index'));
});

it('redirects survey managers to the survey manager from the dashboard route', function () {
    /** @var \Tests\TestCase $this */
    Role::findOrCreate(RoleEnum::Admin->value, 'web');

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('survey-manager.index'));
});
