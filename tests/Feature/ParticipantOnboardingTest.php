<?php

use App\Models\Participant;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows required onboarding to participants who have not completed it', function () {
    loginParticipantAs(Participant::factory()->create([
        'onboarded_at' => null,
    ]));

    get(route('surveys.index'))
        ->assertOk()
        ->assertSee('Welkom bij LIC Feedback')
        ->assertSee('Deze uitleg moet je eenmalig afronden');
});

it('marks participant onboarding as completed', function () {
    $participant = Participant::factory()->create([
        'onboarded_at' => null,
    ]);

    loginParticipantAs($participant);

    post(route('student.onboarding.complete'))
        ->assertRedirect();

    expect($participant->fresh()->onboarded_at)->not->toBeNull();
});

it('does not show onboarding after it has been completed', function () {
    loginParticipantAs(Participant::factory()->create([
        'onboarded_at' => now(),
    ]));

    get(route('surveys.index'))
        ->assertOk()
        ->assertDontSee('Welkom bij LIC Feedback')
        ->assertDontSee('Deze uitleg moet je eenmalig afronden');
});
