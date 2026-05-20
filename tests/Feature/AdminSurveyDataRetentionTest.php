<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('lets admins update retention years from admin surveys page', function () {
    $admin = User::factory()->admin()->createOne();

    config()->set('surveys.retention_years', 5);

    actingAs($admin);

    Livewire::test('pages::admin.surveys.index')
        ->set('retentionYears', 6)
        ->call('saveRetentionYears')
        ->assertHasNoErrors();

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('Ingestelde bewaartermijn: 6 jaar');
});

it('shows retention period to lic employees as read-only', function () {
    $employee = User::factory()->licEmployee()->createOne();

    config()->set('surveys.retention_years', 5);

    actingAs($employee);

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('Ingestelde bewaartermijn: 5 jaar')
        ->assertSee('alleen administratoren kunnen deze waarden aanpassen');

    Livewire::test('pages::admin.surveys.index')
        ->set('retentionYears', 6)
        ->call('saveRetentionYears')
        ->assertForbidden();
});

it('prunes expired responses and deletes related feedback and personal data', function () {
    $admin = User::factory()->admin()->createOne();

    config()->set('surveys.retention_years', 5);

    $survey = Survey::factory()->createOne();
    $question = SurveyQuestion::factory()->for($survey)->createOne();

    $expiredResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => participantIdForRetainedResponse(),
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subYears(6),
    ]);

    $expiredAnswer = SurveyAnswer::create([
        'survey_response_id' => $expiredResponse->id,
        'survey_question_id' => $question->id,
        'answer' => 'Old answer',
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $expiredResponse->id,
        'email' => 'old@example.com',
    ]);

    $activeResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => participantIdForRetainedResponse(),
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subYears(4),
    ]);

    $activeAnswer = SurveyAnswer::create([
        'survey_response_id' => $activeResponse->id,
        'survey_question_id' => $question->id,
        'answer' => 'Recent answer',
    ]);

    actingAs($admin);

    artisan('app:prune-survey-answers')
        ->assertSuccessful();

    assertDatabaseMissing('survey_responses', [
        'id' => $expiredResponse->id,
    ]);

    assertDatabaseMissing('survey_answers', [
        'id' => $expiredAnswer->id,
    ]);

    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $expiredResponse->id,
    ]);

    assertDatabaseHas('survey_responses', [
        'id' => $activeResponse->id,
    ]);

    assertDatabaseHas('survey_answers', [
        'id' => $activeAnswer->id,
    ]);
});

it('prunes using created_at fallback when submitted_at is missing', function () {
    config()->set('surveys.retention_years', 5);

    $survey = Survey::factory()->createOne();
    $question = SurveyQuestion::factory()->for($survey)->createOne();

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => null,
    ]);

    $response->forceFill([
        'created_at' => now()->subYears(6),
        'updated_at' => now()->subYears(6),
    ])->save();

    $answer = SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'answer' => 'Answer',
    ]);

    artisan('app:prune-survey-answers')
        ->assertSuccessful();

    assertDatabaseMissing('survey_responses', [
        'id' => $response->id,
    ]);

    assertDatabaseMissing('survey_answers', [
        'id' => $answer->id,
    ]);
});
