<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerRetentionSetting;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('lets admins edit the survey answer retention lookup value', function () {
    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    Livewire::test('pages::admin.surveys.index')
        ->set('autoDeleteAfterDays', 21)
        ->call('saveAutoDeleteAfterDays')
        ->assertHasNoErrors();

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('Ingestelde bewaartermijn: 21 dagen');
});

it('deletes responses that fall outside a newly shortened retention period', function () {
    $admin = User::factory()->admin()->createOne();
    SurveyAnswerRetentionSetting::create([
        'auto_delete_after_days' => 30,
    ]);

    $survey = Survey::factory()->createOne();
    $question = SurveyQuestion::factory()->for($survey)->createOne();
    $expiredAfterShortening = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subDays(10),
        'delete_on_date' => now()->addDays(20)->toDateString(),
    ]);

    SurveyAnswer::create([
        'survey_response_id' => $expiredAfterShortening->id,
        'survey_question_id' => $question->id,
        'answer' => 'Old answer',
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $expiredAfterShortening->id,
        'email' => 'old@example.com',
    ]);

    $stillActive = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subDays(5),
        'delete_on_date' => now()->addDays(10)->toDateString(),
    ]);

    SurveyAnswer::create([
        'survey_response_id' => $stillActive->id,
        'survey_question_id' => $question->id,
        'answer' => 'Recent answer',
    ]);

    actingAs($admin);

    Livewire::test('pages::admin.surveys.index')
        ->set('autoDeleteAfterDays', 7)
        ->call('saveAutoDeleteAfterDays')
        ->assertHasNoErrors();

    assertDatabaseMissing('survey_responses', [
        'id' => $expiredAfterShortening->id,
    ]);

    assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $expiredAfterShortening->id,
    ]);

    $remainingResponse = SurveyResponse::query()->findOrFail($stillActive->id);

    expect($remainingResponse->delete_on_date?->toDateString())
        ->toBe(now()->subDays(5)->addDays(7)->toDateString());
});

it('keeps existing response delete dates unchanged when retention is extended', function () {
    $admin = User::factory()->admin()->createOne();
    SurveyAnswerRetentionSetting::create([
        'auto_delete_after_days' => 7,
    ]);

    $survey = Survey::factory()->createOne();
    $question = SurveyQuestion::factory()->for($survey)->createOne();
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subDays(5),
        'delete_on_date' => now()->addDays(2)->toDateString(),
    ]);

    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'answer' => 'Answer',
    ]);

    actingAs($admin);

    Livewire::test('pages::admin.surveys.index')
        ->set('autoDeleteAfterDays', 30)
        ->call('saveAutoDeleteAfterDays')
        ->assertHasNoErrors();

    $unchangedResponse = SurveyResponse::query()->findOrFail($response->id);

    expect($unchangedResponse->delete_on_date?->toDateString())
        ->toBe(now()->addDays(2)->toDateString());
});

it('shows retention value to lic employees and explains only admins can edit', function () {
    $employee = User::factory()->licEmployee()->createOne();
    SurveyAnswerRetentionSetting::create([
        'auto_delete_after_days' => 14,
    ]);

    actingAs($employee);

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('Ingestelde bewaartermijn: 14 dagen')
        ->assertSee('alleen administratoren kunnen deze waarden aanpassen');

    Livewire::test('pages::admin.surveys.index')
        ->set('autoDeleteAfterDays', 30)
        ->call('saveAutoDeleteAfterDays')
        ->assertForbidden();
});

it('prunes expired responses and deletes related answers and contact data', function () {
    $survey = Survey::factory()->createOne();
    $question = SurveyQuestion::factory()->for($survey)->createOne();
    $expiredResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subDays(10),
        'delete_on_date' => now()->subDay()->toDateString(),
    ]);

    $expiredAnswer = SurveyAnswer::create([
        'survey_response_id' => $expiredResponse->id,
        'survey_question_id' => $question->id,
        'answer' => 'Old answer',
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $expiredResponse->id,
        'email' => 'expired@example.com',
    ]);

    $activeResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now()->subDays(2),
        'delete_on_date' => now()->addDay()->toDateString(),
    ]);

    $recentAnswer = SurveyAnswer::create([
        'survey_response_id' => $activeResponse->id,
        'survey_question_id' => $question->id,
        'answer' => 'Recent answer',
    ]);

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

    assertDatabaseHas('survey_answers', [
        'id' => $recentAnswer->id,
    ]);

    assertDatabaseHas('survey_responses', [
        'id' => $activeResponse->id,
    ]);
});
