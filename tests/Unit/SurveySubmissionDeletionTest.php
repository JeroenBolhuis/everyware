<?php

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Models\ContactInformationSubmission;
use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createSurveyResponseForDeletion(array $attributes = []): SurveyResponse
{
    $response = SurveyResponse::create([
        'survey_id' => $attributes['survey_id'] ?? Survey::factory()->createOne()->id,
        'participant_id' => $attributes['participant_id'] ?? Participant::factory()->createOne()->id,
        'is_anonymous' => $attributes['is_anonymous'] ?? false,
        'withdrawal_token' => $attributes['withdrawal_token'] ?? (string) Str::uuid(),
        'submitted_at' => array_key_exists('submitted_at', $attributes) ? $attributes['submitted_at'] : now(),
    ]);

    if (isset($attributes['created_at']) || isset($attributes['updated_at'])) {
        $response->forceFill([
            'created_at' => $attributes['created_at'] ?? $response->created_at,
            'updated_at' => $attributes['updated_at'] ?? $response->updated_at,
        ])->save();
    }

    return $response;
}

it('deletes a survey submission and all directly related private data', function () {
    $survey = Survey::factory()->createOne();
    $participant = Participant::factory()->createOne();
    $response = createSurveyResponseForDeletion([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
    ]);
    $question = SurveyQuestion::factory()->createOne(['survey_id' => $survey->id]);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $response->id,
        'name' => 'Jamie',
        'email' => 'jamie@example.com',
    ]);
    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'answer' => 'Antwoord',
    ]);
    ParticipantPointsHistory::create([
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => $response::class,
        'source_id' => $response->id,
        'reason' => 'survey',
    ]);

    (new DeleteSurveySubmission)->handle($response);

    expect(SurveyResponse::query()->whereKey($response->id)->exists())->toBeFalse()
        ->and(ContactInformationSubmission::query()->where('survey_response_id', $response->id)->exists())->toBeFalse()
        ->and(SurveyAnswer::query()->where('survey_response_id', $response->id)->exists())->toBeFalse()
        ->and(ParticipantPointsHistory::query()->where('source_id', $response->id)->exists())->toBeFalse();
});

it('prunes only survey responses older than the configured retention period', function () {
    config(['surveys.retention_years' => 5]);

    $oldSubmitted = createSurveyResponseForDeletion([
        'submitted_at' => now()->subYears(6),
    ]);
    $oldCreatedWithoutSubmission = createSurveyResponseForDeletion([
        'submitted_at' => null,
        'created_at' => now()->subYears(6),
        'updated_at' => now()->subYears(6),
    ]);
    $recentResponse = createSurveyResponseForDeletion([
        'submitted_at' => now()->subYear(),
    ]);

    $this->artisan('app:prune-survey-answers')
        ->assertSuccessful();

    expect(SurveyResponse::query()->whereKey($oldSubmitted->id)->exists())->toBeFalse()
        ->and(SurveyResponse::query()->whereKey($oldCreatedWithoutSubmission->id)->exists())->toBeFalse()
        ->and(SurveyResponse::query()->whereKey($recentResponse->id)->exists())->toBeTrue();
});
