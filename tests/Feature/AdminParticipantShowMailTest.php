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
use function Pest\Laravel\get;

function createParticipantResponse(Participant $participant, bool $isAnonymous, string $title): SurveyResponse
{
    $survey = Survey::factory()->active()->create(['title' => $title]);

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Waarom?',
        'type' => 'textarea',
        'options' => null,
        'required' => true,
        'sort_order' => 1,
    ]);

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => $isAnonymous,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now(),
    ]);

    $response->answers()->create([
        'survey_question_id' => $survey->questions()->firstOrFail()->id,
        'answer' => 'Antwoord',
    ]);

    return $response;
}

it('shows anonymous state per participant response and only offers mail for non anonymous responses', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('student@example.com')->createOne();

    createParticipantResponse($participant, true, 'Anonieme ronde');
    $nonAnonymousResponse = createParticipantResponse($participant, false, 'Niet anonieme ronde');

    actingAs($employee);

    get(route('admin.participants.show', $participant))
        ->assertOk()
        ->assertSee('Anoniem')
        ->assertSee('Niet anoniem')
        ->assertSee('Mailen')
        ->assertSee($nonAnonymousResponse->survey->title)
        ->assertDontSee('student@example.com');
});

it('lets lic employees mail a non anonymous participant response without seeing the email', function () {
    Mail::fake();

    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('student@example.com')->createOne();
    $response = createParticipantResponse($participant, false, 'Niet anonieme ronde');

    actingAs($employee);

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('mailSubject', 'Vraag over je inzending')
        ->set('mailMessage', 'Kun je contact opnemen?')
        ->call('sendResponseMessage', $response->id)
        ->assertHasNoErrors();

    Mail::assertSent(AdminParticipantMessageMail::class, function (AdminParticipantMessageMail $mail) {
        return $mail->hasTo('student@example.com')
            && $mail->subjectLine === 'Vraag over je inzending'
            && $mail->messageBody === 'Kun je contact opnemen?';
    });
});

it('does not mail anonymous participant responses', function () {
    Mail::fake();

    $employee = User::factory()->licEmployee()->createOne();
    $participant = Participant::factory()->withEmail('student@example.com')->createOne();
    $response = createParticipantResponse($participant, true, 'Anonieme ronde');

    actingAs($employee);

    Livewire::test('pages::admin.participants.show', ['participant' => $participant])
        ->set('mailSubject', 'Vraag')
        ->set('mailMessage', 'Bericht')
        ->call('sendResponseMessage', $response->id)
        ->assertNotFound();

    Mail::assertNothingSent();
});
