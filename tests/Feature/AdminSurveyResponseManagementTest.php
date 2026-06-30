<?php

use App\Mail\AdminParticipantMessageMail;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
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
    $participant = Participant::factory()->withEmail('jamie@example.com')->create();

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

it('shows participant public code for non anonymous responses without email', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);
    $participant = participantByEmail('jamie@example.com');

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee($participant->public_code)
        ->assertDontSee('jamie@example.com')
        ->assertSee('Very helpful and practical.');
});

it('shows when no contact information was provided', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee($response->participant->public_code)
        ->assertDontSee('jamie@example.com');
});
it('hides participant email from lic employees for non anonymous responses', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertDontSee('Jamie Jansen')
        ->assertDontSee('+31612345678')
        ->assertDontSee('jamie@example.com')
        ->assertSee($response->participant->public_code)
        ->assertSee('Very helpful and practical.');
});

it('lets lic employees mail a non anonymous respondent from the response page without seeing the email', function () {
    Mail::fake();

    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Niet anoniem')
        ->assertSee('Student mailen')
        ->assertDontSee('jamie@example.com');

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->set('mailSubject', 'Vraag over je inzending')
        ->set('mailMessage', 'Kun je contact opnemen?')
        ->call('sendRespondentMessage')
        ->assertHasNoErrors();

    Mail::assertSent(AdminParticipantMessageMail::class, function (AdminParticipantMessageMail $mail) {
        return $mail->hasTo('jamie@example.com')
            && $mail->subjectLine === 'Vraag over je inzending'
            && $mail->messageBody === 'Kun je contact opnemen?';
    });
});

it('does not allow mailing anonymous respondents from the response page', function () {
    Mail::fake();

    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => true])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Anoniem')
        ->assertDontSee('Student mailen')
        ->assertDontSee('jamie@example.com');

    Mail::assertNothingSent();
});
it('lets lic employees delete a full submission and shows a success message', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $participant = participantByEmail('jamie@example.com');

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

it('lets admins block a participant by public code without deleting the current submission', function () {
    $admin = User::factory()->admin()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($admin);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Deelnemer blokkeren')
        ->assertSee('Blokkeren');

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->call('blockRespondent')
        ->assertHasNoErrors();

    expect(participantByEmail('jamie@example.com')?->blocked_at)->not->toBeNull();
    expect($response->fresh())->not->toBeNull();
});
it('hides the email block action from lic employees for non anonymous responses', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createReviewableSurvey();
    $response = createReviewableResponse($survey);

    $response->forceFill(['is_anonymous' => false])->save();

    actingAs($employee);

    get(route('admin.responses.show', $response))
        ->assertOk()
        ->assertSee('Deelnemer blokkeren')
        ->assertDontSee('E-mailadres blokkeren');

    Livewire::test('pages::admin.responses.show', ['response' => $response])
        ->call('blockRespondent')
        ->assertHasNoErrors();

    expect(participantByEmail('jamie@example.com')?->blocked_at)->not->toBeNull();
});
