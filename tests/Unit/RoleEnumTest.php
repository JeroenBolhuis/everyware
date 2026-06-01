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
    expect(Role::Admin->description())->toContain('persoonsgegevens beheren');
    expect(Role::LicEmployee->description())->toContain('zonder toegang tot persoonsgegevens');
    expect(Role::User->description())->toContain('zonder toegang');
});

it('returns the full permission display list', function () {
    expect(Role::permissions())->toBe([
        'account_access' => 'Eigen account gebruiken',
        'manage_surveys' => 'Enquêtes aanmaken, bewerken en sluiten',
        'review_responses' => 'Inzendingen en antwoorden bekijken',
        'delete_responses' => 'Volledige inzendingen verwijderen',
        'export_anonymized_feedback' => 'Exports zonder persoonsgegevens maken',
        'view_participant_points' => 'Deelnemers als pseudoniem met punten bekijken',
        'correct_points' => 'Punten afboeken of corrigeren',
        'view_personal_data' => 'Persoonsgegevens en e-mailadressen bekijken',
        'block_email_addresses' => 'E-mailadressen blokkeren',
        'manage_retention' => 'Bewaartermijn aanpassen',
        'manage_users' => 'Gebruikersaccounts beheren',
        'assign_roles' => 'Rollen toewijzen',
    ]);
});

it('knows which permissions each role grants', function () {
    expect(Role::Admin->grantsPermission('assign_roles'))->toBeTrue()
        ->and(Role::Admin->grantsPermission('correct_points'))->toBeTrue()
        ->and(Role::LicEmployee->grantsPermission('correct_points'))->toBeTrue()
        ->and(Role::LicEmployee->grantsPermission('export_anonymized_feedback'))->toBeTrue()
        ->and(Role::LicEmployee->grantsPermission('view_personal_data'))->toBeFalse()
        ->and(Role::LicEmployee->grantsPermission('block_email_addresses'))->toBeFalse()
        ->and(Role::LicEmployee->grantsPermission('manage_retention'))->toBeFalse()
        ->and(Role::LicEmployee->grantsPermission('assign_roles'))->toBeFalse()
        ->and(Role::User->grantsPermission('account_access'))->toBeTrue()
        ->and(Role::User->grantsPermission('manage_surveys'))->toBeFalse();
});
