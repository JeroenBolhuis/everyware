<?php

use App\Models\User;
use Tests\TestCase;

it('redirects guests to the login page', function () {
    /** @var TestCase $this */
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

it('redirects authenticated users to the public surveys overview from the dashboard route', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('surveys.index'));
});

it('redirects survey managers to the survey manager from the dashboard route', function () {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('survey-manager.index'));
});
