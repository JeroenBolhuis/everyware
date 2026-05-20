<?php

use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

it('requires participant authentication', function () {
    get(route('student.points'))
        ->assertRedirect(route('survey.participant.login', ['redirect' => '/student/punten']));
});

it('shows the participant point balance and completed surveys', function () {
    $participant = Participant::factory()->create([
        'email' => 'student@example.com',
        'current_points' => 10,
    ]);

    $surveyWithPoints = Survey::factory()->create(['title' => 'Loopbaan enquete']);
    $anonymousSurvey = Survey::factory()->create(['title' => 'Anonieme enquete']);

    $responseWithPoints = SurveyResponse::create([
        'survey_id' => $surveyWithPoints->id,
        'participant_id' => $participant->id,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $surveyWithPoints->id,
        'survey_response_id' => $responseWithPoints->id,
        'name' => 'Student Naam',
        'email' => $participant->email,
    ]);

    ParticipantPointsHistory::create([
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => SurveyResponse::class,
        'source_id' => $responseWithPoints->id,
    ]);

    SurveyResponse::create([
        'survey_id' => $anonymousSurvey->id,
        'participant_id' => $participant->id,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now()->subDay(),
    ]);

    loginParticipantAs($participant);

    get(route('student.points'))
        ->assertOk()
        ->assertSee('10 punten')
        ->assertSee('Loopbaan enquete')
        ->assertSee('Anonieme enquete')
        ->assertSee('Anoniem ingevuld');
});
