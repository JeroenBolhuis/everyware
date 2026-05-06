<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds initials from the first two name parts', function () {
    $user = new User([
        'name' => 'Jamie van Dijk',
    ]);

    expect($user->initials())->toBe('Jv');
});

it('gives admins access to all admin survey capabilities', function () {
    $user = User::factory()->admin()->createOne();

    expect($user->isAdmin())->toBeTrue();
    expect($user->canManageUsers())->toBeTrue();
    expect($user->canManageSurveys())->toBeTrue();
    expect($user->canReviewSurveyResponses())->toBeTrue();
    expect($user->canAccessAdminArea())->toBeTrue();
});

it('gives lic employees survey access but not user management access', function () {
    $user = User::factory()->licEmployee()->createOne();

    expect($user->isAdmin())->toBeFalse();
    expect($user->isLicEmployee())->toBeTrue();
    expect($user->isLicMedewerker())->toBeTrue();
    expect($user->canManageUsers())->toBeFalse();
    expect($user->canManageSurveys())->toBeTrue();
    expect($user->canReviewSurveyResponses())->toBeTrue();
    expect($user->canAccessAdminArea())->toBeTrue();
});

it('keeps regular users out of the admin area', function () {
    $user = User::factory()->createOne();

    expect($user->isAdmin())->toBeFalse();
    expect($user->isLicEmployee())->toBeFalse();
    expect($user->canManageUsers())->toBeFalse();
    expect($user->canManageSurveys())->toBeFalse();
    expect($user->canReviewSurveyResponses())->toBeFalse();
    expect($user->canAccessAdminArea())->toBeFalse();
});
