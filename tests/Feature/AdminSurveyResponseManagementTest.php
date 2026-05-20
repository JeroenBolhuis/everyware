<?php

use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
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
    $participant = Participant::factory()->create(['email' => 'jamie@example.com']);

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
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
    $user = User::factory()->createOne();

    /** @var User $user */
    actingAs($user);

    get(route('admin.surveys.index'))->assertForbidden();
});

it('lets lic employees manage surveys', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();

    actingAs($employee);

    get(route('survey-manager.index'))
        ->assertOk()
        ->assertSee($survey->title);

    get(route('survey-manager.create'))
        ->assertOk()
        ->assertSee('Nieuwe enquête aanmaken');
});

it('lets lic employees open the survey response overview', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();

    actingAs($employee);

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee($survey->title);
});

it('shows participant email to admins for non anonymous responses', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('jamie@example.com')
        ->assertSee('Very helpful and practical.');
});

it('shows when no contact information was provided', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Deze inzending is anoniem. De deelnemer en het e-mailadres worden niet getoond.');
});
it('shows participant email to lic employees for non anonymous responses', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertDontSee('Jamie Jansen')
        ->assertDontSee('+31612345678')
        ->assertSee('jamie@example.com')
        ->assertSee('Very helpful and practical.');
});
it('lets lic employees delete a full submission and shows a success message', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $participant = Participant::firstOrCreate([
        'email' => 'jamie@example.com',
    ]);

    $response->update([
        'participant_id' => $participant->id,
        'is_anonymous' => false,
    ]);

    $secondAnswer = $response->answers()->create([
        'survey_question_id' => $survey->questions()->firstOrFail()->id,
        'answer' => 'Dit antwoord hoort ook verwijderd te worden.',
    ]);

    $pointsHistory = $response->participantPointsHistories()->create([
        'participant_id' => $participant->id,
        'amount' => 10,
    ]);

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Inzending verwijderen')
        ->assertSee('Volledige inzending verwijderen?')
        ->assertSee('Definitief verwijderen')
        ->assertDontSee('Antwoord verwijderen');

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->call('deleteSubmission')
        ->assertRedirect(route('admin.surveys.show', $survey));

    expect(session('status'))->toBe('De inzending is succesvol verwijderd.');

    assertDatabaseMissing('survey_responses', [
        'id' => $response->id,
    ]);

    assertDatabaseMissing('survey_answers', [
        'id' => $secondAnswer->id,
    ]);

    assertDatabaseMissing('participant_points_history', [
        'id' => $pointsHistory->id,
    ]);

    get(route('admin.surveys.show', $survey))
        ->assertOk()
        ->assertSee('De inzending is succesvol verwijderd.');
});

it('lets admins block an email address and delete the current submission', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('E-mailadres blokkeren')
        ->assertSee('Blokkeren en verwijderen');

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->call('blockRespondent')
        ->assertRedirect(route('admin.surveys.show', $survey));

    expect(session('status'))->toBe('De inzending is verwijderd en het e-mailadres is geblokkeerd.');

    assertDatabaseHas('participants', [
        'email' => 'jamie@example.com',
    ]);

    assertDatabaseMissing('survey_responses', [
        'id' => $response->id,
    ]);

    expect(Participant::where('email', 'jamie@example.com')->firstOrFail()->blocked_at)->not->toBeNull();
});
it('shows the email block action to lic employees for non anonymous responses', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('E-mailadres blokkeren')
        ->assertSee('Blokkeren en verwijderen');
});
