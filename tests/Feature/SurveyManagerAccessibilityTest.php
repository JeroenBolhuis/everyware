<?php

use App\Enums\Role as RoleEnum;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function actingAsLicManager(): User
{
    Role::findOrCreate(RoleEnum::LicEmployee->value, 'web');

    $user = User::factory()->createOne();
    $user->assignRole(RoleEnum::LicEmployee->value);

    actingAs($user);

    return $user;
}

it('renders accessible status regions and labelled actions on survey manager index', function () {
    actingAsLicManager();

    $survey = Survey::factory()->createOne([
        'title' => 'Toegankelijke Enquete',
        'is_active' => true,
    ]);

    $this->withSession(['status' => 'Teststatus'])
        ->get(route('survey-manager.index'))
        ->assertOk()
        ->assertSee('id="survey-manager-page-title"', false)
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee('aria-label="Nieuwe enquête aanmaken"', false)
        ->assertSee('aria-label="Bewerk enquête '.$survey->title.'"', false)
        ->assertSee('aria-label="Sluit enquête '.$survey->title.'"', false)
        ->assertSee('aria-label="Open actieve enquête '.$survey->title.' in nieuw tabblad"', false);
});

it('renders accessible form helper and live announcement target on create page', function () {
    actingAsLicManager();

    get(route('survey-manager.create'))
        ->assertOk()
        ->assertSee('id="survey-manager-form"', false)
        ->assertSee('aria-describedby="survey-manager-form-help survey-manager-a11y-status"', false)
        ->assertSee('id="survey-manager-form-help"', false)
        ->assertSee('id="survey-manager-a11y-status"', false)
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false);
});

it('renders the same accessible form semantics on edit page', function () {
    actingAsLicManager();

    $survey = Survey::factory()->createOne();

    get(route('survey-manager.edit', $survey))
        ->assertOk()
        ->assertSee('id="survey-manager-form"', false)
        ->assertSee('id="survey-manager-a11y-status"', false)
        ->assertSee('aria-describedby="survey-manager-form-help survey-manager-a11y-status"', false);
});
