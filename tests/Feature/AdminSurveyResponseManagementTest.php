<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function createReviewableSurvey(): Survey
{
    $survey = Survey::factory()->active()->create([
        'title' => 'Reviewable Survey',
    ]);

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'How was the workshop?',
        'type' => 'textarea',
        'options' => null,
        'required' => true,
        'sort_order' => 1,
    ]);

    return $survey;
}

function createReviewableResponse(Survey $survey): SurveyResponse
{
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now(),
    ]);

    $response->answers()->create([
        'survey_question_id' => $survey->questions()->firstOrFail()->id,
        'answer' => 'Very helpful and practical.',
    ]);

    return $response->fresh('answers.question', 'survey');
}

it('forbids regular users from the admin survey review area', function () {
    $user = User::factory()->create();
    actingAs($user);

    get(route('admin.surveys.index'))->assertForbidden();
});

it('lets lic employees open the survey response overview', function () {
    $employee = User::factory()->licEmployee()->create();
    $survey = createReviewableSurvey();

    actingAs($employee);

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee($survey->title);
});

it('shows decrypted contact details to admins and lic employees', function () {
    $employee = User::factory()->licEmployee()->create();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    ContactInformationSubmission::create([
        'survey_id' => $survey->id,
        'survey_response_id' => $response->id,
        'name' => 'Jamie Jansen',
        'email' => 'jamie@example.com',
        'phone' => '+31612345678',
    ]);

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Jamie Jansen')
        ->assertSee('jamie@example.com')
        ->assertSee('+31612345678')
        ->assertSee('Very helpful and practical.');
});

it('shows when no contact information was provided', function () {
    $admin = User::factory()->admin()->create();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Er zijn geen contactgegevens gedeeld voor deze inzending.');
});

it('lets lic employees delete answers and shows a success message', function () {
    $employee = User::factory()->licEmployee()->create();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $answerToKeep = $response->answers()->create([
        'survey_question_id' => $survey->questions()->firstOrFail()->id,
        'answer' => 'Dit antwoord blijft staan.',
    ]);

    $answerToDelete = SurveyAnswer::query()
        ->where('survey_response_id', $response->id)
        ->where('answer', 'Very helpful and practical.')
        ->firstOrFail();

    actingAs($employee);

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->call('deleteAnswer', $answerToDelete->id)
        ->assertSet('statusMessage', 'Het antwoord is succesvol verwijderd.')
        ->assertDontSee('Very helpful and practical.')
        ->assertSee('Dit antwoord blijft staan.')
        ->assertSee('Het antwoord is succesvol verwijderd.');

    assertDatabaseMissing('survey_answers', [
        'id' => $answerToDelete->id,
    ]);

    expect($answerToKeep->fresh())->not->toBeNull();
});
