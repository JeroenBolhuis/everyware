<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns labels for each role', function () {
    expect(Role::Admin->label())->toBe('Beheerder');
    expect(Role::LicEmployee->label())->toBe('LIC-medewerker');
    expect(Role::User->label())->toBe('Gebruiker');
});

it('returns a description for each role', function () {
    expect(Role::Admin->description())->toContain('rollen toewijzen');
    expect(Role::LicEmployee->description())->toContain('enquete aanmaken');
    expect(Role::User->description())->toContain('geen toegang');
});
