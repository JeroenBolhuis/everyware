<?php

use App\Models\Participant;
use App\Models\ParticipantIdentity;
use App\Models\User;
use App\Services\ParticipantService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores participant email only on the personal identity table', function () {
    $participant = app(ParticipantService::class)->findOrCreateByEmail(' Jamie@Example.com ');

    expect(Schema::hasColumn('participants', 'email'))->toBeFalse()
        ->and($participant)->toBeInstanceOf(Participant::class)
        ->and($participant->academy)->toBeNull()
        ->and(ParticipantIdentity::where('participant_id', $participant->id)->value('email'))->toBe('jamie@example.com');
});

it('returns participant email only to admins', function () {
    $participant = Participant::factory()->withEmail('jamie@example.com')->createOne();
    $admin = User::factory()->admin()->createOne();
    $employee = User::factory()->licEmployee()->createOne();
    $service = app(ParticipantService::class);

    expect($service->emailForAdmin($admin, $participant->id))->toBe('jamie@example.com');

    $service->emailForAdmin($employee, $participant->id);
})->throws(AuthorizationException::class);
