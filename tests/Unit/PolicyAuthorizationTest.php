<?php

use App\Enums\Role;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Policies\ParticipantPolicy;
use App\Policies\SurveyPolicy;
use App\Policies\SurveyResponsePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows admins and lic employees to work with participants', function () {
    $admin = User::factory()->admin()->createOne();
    $employee = User::factory()->licEmployee()->createOne();
    $user = User::factory()->createOne();
    $participant = Participant::factory()->createOne();
    $policy = new ParticipantPolicy;

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $participant))->toBeTrue()
        ->and($policy->correctPoints($admin, $participant))->toBeTrue()
        ->and($policy->viewAny($employee))->toBeTrue()
        ->and($policy->view($employee, $participant))->toBeTrue()
        ->and($policy->correctPoints($employee, $participant))->toBeTrue()
        ->and($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $participant))->toBeFalse()
        ->and($policy->correctPoints($user, $participant))->toBeFalse();
});

it('allows only survey reviewers to view surveys and responses', function () {
    $admin = User::factory()->admin()->createOne();
    $employee = User::factory()->licEmployee()->createOne();
    $user = User::factory()->createOne();
    $survey = Survey::factory()->createOne();
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => Participant::factory()->createOne()->id,
        'is_anonymous' => false,
        'withdrawal_token' => (string) Str::uuid(),
    ]);

    $surveyPolicy = new SurveyPolicy;
    $responsePolicy = new SurveyResponsePolicy;

    expect($surveyPolicy->viewAny($admin))->toBeTrue()
        ->and($surveyPolicy->view($admin, $survey))->toBeTrue()
        ->and($surveyPolicy->viewAny($employee))->toBeTrue()
        ->and($surveyPolicy->view($employee, $survey))->toBeTrue()
        ->and($surveyPolicy->viewAny($user))->toBeFalse()
        ->and($surveyPolicy->view($user, $survey))->toBeFalse()
        ->and($responsePolicy->view($admin, $response))->toBeTrue()
        ->and($responsePolicy->delete($admin, $response))->toBeTrue()
        ->and($responsePolicy->view($employee, $response))->toBeTrue()
        ->and($responsePolicy->delete($employee, $response))->toBeTrue()
        ->and($responsePolicy->view($user, $response))->toBeFalse()
        ->and($responsePolicy->delete($user, $response))->toBeFalse();
});

it('allows admins to manage users but protects the current and last admin from deletion', function () {
    $admin = User::factory()->admin()->createOne();
    $otherAdmin = User::factory()->admin()->createOne();
    $user = User::factory()->createOne();
    $policy = new UserPolicy;

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $user))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $user))->toBeTrue()
        ->and($policy->delete($admin, $user))->toBeTrue()
        ->and($policy->delete($admin, $admin))->toBeFalse()
        ->and($policy->delete($admin, $otherAdmin))->toBeTrue()
        ->and($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $admin))->toBeFalse()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $admin))->toBeFalse()
        ->and($policy->delete($user, $admin))->toBeFalse();
});

it('prevents deleting the last remaining admin', function () {
    $admin = User::factory()->admin()->createOne();
    $target = User::factory()->admin()->createOne();
    $policy = new UserPolicy;

    $admin->removeRole(Role::Admin->value);

    expect($policy->delete($admin->fresh(), $target->fresh()))->toBeFalse();
});
