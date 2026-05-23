<?php

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('defines survey answer relationships', function () {
    $survey = Survey::factory()->createOne();
    $participant = Participant::factory()->createOne();
    $question = SurveyQuestion::factory()->createOne(['survey_id' => $survey->id]);
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => true,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ]);
    $answer = SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'answer' => 'Ja',
    ]);

    expect($answer->response())->toBeInstanceOf(BelongsTo::class)
        ->and($answer->question())->toBeInstanceOf(BelongsTo::class)
        ->and($answer->response->is($response))->toBeTrue()
        ->and($answer->question->is($question))->toBeTrue();
});

it('defines participant points history relationships', function () {
    $survey = Survey::factory()->createOne();
    $participant = Participant::factory()->createOne();
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => true,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ]);
    $history = ParticipantPointsHistory::create([
        'participant_id' => $participant->id,
        'amount' => 10,
        'source_type' => $response::class,
        'source_id' => $response->id,
        'reason' => 'survey',
    ]);

    expect($history->participant())->toBeInstanceOf(BelongsTo::class)
        ->and($history->source())->toBeInstanceOf(MorphTo::class)
        ->and($history->participant->is($participant))->toBeTrue()
        ->and($history->source->is($response))->toBeTrue()
        ->and($history->amount)->toBe(10);
});
