<?php

use App\Mail\AdminParticipantMessageMail;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function createSurveyWithRespondent(string $title, string $email, ?string $submittedAt = null, ?Participant $participant = null): array
{
    $survey = Survey::factory()->createOne([
        'title' => $title,
        'created_at' => $submittedAt ?? now(),
        'updated_at' => $submittedAt ?? now(),
    ]);

    $participant ??= Participant::factory()->withEmail($email)->createOne();

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => $submittedAt ? Carbon::parse($submittedAt) : now(),
    ]);

    return [$survey, $participant, $response];
}

it('shows the participant mail page to admins without exposing addresses', function () {
    $admin = User::factory()->admin()->createOne();
    createSurveyWithRespondent('Meest recente enquete', 'student@example.com', '2026-05-04 10:00:00');

    actingAs($admin);

    get(route('admin.participants.mail'))
        ->assertOk()
        ->assertSee('Mailen')
        ->assertSee('Deelnemers mailen')
        ->assertSee('Dubbele e-mailadressen worden niet toegevoegd')
        ->assertSee('Meest recente enquete')
        ->assertDontSee('Beheer gebruikers en toegangsrechten')
        ->assertDontSee('student@example.com');
});

it('forbids lic employees from participant mailing lists', function () {
    $employee = User::factory()->licEmployee()->createOne();

    actingAs($employee);

    get(route('admin.participants.mail'))->assertForbidden();
});

it('adds survey respondent lists and sends one mail per unique address', function () {
    Mail::fake();

    $admin = User::factory()->admin()->createOne();
    $sharedParticipant = Participant::factory()->withEmail('student@example.com')->createOne();
    [$firstSurvey] = createSurveyWithRespondent('Eerste enquete', 'student@example.com', '2026-05-01 10:00:00', $sharedParticipant);
    [$secondSurvey] = createSurveyWithRespondent('Tweede enquete', 'student@example.com', '2026-05-02 10:00:00', $sharedParticipant);
    [$thirdSurvey] = createSurveyWithRespondent('Derde enquete', 'ander@example.com', '2026-05-03 10:00:00');

    actingAs($admin);

    Livewire::test('pages::admin.participants.mail')
        ->call('addSurvey', $firstSurvey->id)
        ->assertSet('selectedSurveyIds', [$firstSurvey->id])
        ->call('addSurvey', $secondSurvey->id)
        ->call('addSurvey', $thirdSurvey->id)
        ->assertSee('2 unieke ontvangers')
        ->assertSee('1 dubbele ontvanger')
        ->set('subject', 'Nieuwe enquete beschikbaar')
        ->set('message', 'Wil je de nieuwe enquete invullen?')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('participant-mails-sent');

    Mail::assertSent(AdminParticipantMessageMail::class, 2);
    Mail::assertSent(AdminParticipantMessageMail::class, fn (AdminParticipantMessageMail $mail) => $mail->hasTo('student@example.com'));
    Mail::assertSent(AdminParticipantMessageMail::class, fn (AdminParticipantMessageMail $mail) => $mail->hasTo('ander@example.com'));
});

it('requires a selected respondent list before sending', function () {
    Mail::fake();

    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    Livewire::test('pages::admin.participants.mail')
        ->set('subject', 'Nieuwe enquete beschikbaar')
        ->set('message', 'Wil je de nieuwe enquete invullen?')
        ->call('send')
        ->assertHasErrors(['selectedSurveyIds']);

    Mail::assertNothingSent();
});

it('lets admins add a manual email address without showing it in the interface', function () {
    Mail::fake();

    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    Livewire::test('pages::admin.participants.mail')
        ->set('manualEmail', 'Handmatig@Example.com')
        ->call('addManualEmail')
        ->assertSet('manualEmail', '')
        ->assertSet('manualEmails', ['handmatig@example.com'])
        ->assertSee('1 unieke ontvangers')
        ->assertSee('1 handmatig toegevoegde ontvanger')
        ->assertDontSee('handmatig@example.com')
        ->set('subject', 'Nieuwe enquete beschikbaar')
        ->set('message', 'Wil je de nieuwe enquete invullen?')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('participant-mails-sent');

    Mail::assertSent(AdminParticipantMessageMail::class, 1);
    Mail::assertSent(AdminParticipantMessageMail::class, fn (AdminParticipantMessageMail $mail) => $mail->hasTo('handmatig@example.com'));
});

it('deduplicates manual addresses against selected survey respondents', function () {
    Mail::fake();

    $admin = User::factory()->admin()->createOne();
    [$survey] = createSurveyWithRespondent('Eerdere enquete', 'student@example.com', '2026-05-01 10:00:00');

    actingAs($admin);

    Livewire::test('pages::admin.participants.mail')
        ->call('addSurvey', $survey->id)
        ->set('manualEmail', 'Student@Example.com')
        ->call('addManualEmail')
        ->assertSee('1 unieke ontvangers')
        ->assertSee('1 dubbele ontvanger')
        ->set('subject', 'Nieuwe enquete beschikbaar')
        ->set('message', 'Wil je de nieuwe enquete invullen?')
        ->call('send')
        ->assertHasNoErrors();

    Mail::assertSent(AdminParticipantMessageMail::class, 1);
    Mail::assertSent(AdminParticipantMessageMail::class, fn (AdminParticipantMessageMail $mail) => $mail->hasTo('student@example.com'));
});

it('can include an active survey link in participant emails', function () {
    Mail::fake();

    $admin = User::factory()->admin()->createOne();
    [$recipientSurvey] = createSurveyWithRespondent('Eerdere enquete', 'student@example.com', '2026-05-01 10:00:00');
    $linkedSurvey = Survey::factory()->active()->createOne([
        'title' => 'Nieuwe ronde',
    ]);

    actingAs($admin);

    get(route('admin.participants.mail'))
        ->assertOk()
        ->assertSee('Enquetelink toevoegen')
        ->assertSee('Nieuwe ronde');

    Livewire::test('pages::admin.participants.mail')
        ->call('addSurvey', $recipientSurvey->id)
        ->set('linkedSurveyId', $linkedSurvey->id)
        ->set('subject', 'Nieuwe enquete beschikbaar')
        ->set('message', 'Wil je de nieuwe enquete invullen?')
        ->call('send')
        ->assertHasNoErrors();

    Mail::assertSent(AdminParticipantMessageMail::class, function (AdminParticipantMessageMail $mail) use ($linkedSurvey) {
        return $mail->hasTo('student@example.com')
            && $mail->surveyUrl === route('survey.share.show', $linkedSurvey->share_token)
            && $mail->surveyTitle === 'Nieuwe ronde';
    });
});

it('does not offer closed surveys as mail links', function () {
    $admin = User::factory()->admin()->createOne();
    Survey::factory()->inactive()->createOne([
        'title' => 'Gesloten ronde',
    ]);

    actingAs($admin);

    get(route('admin.participants.mail'))
        ->assertOk()
        ->assertDontSee('Gesloten ronde');
});
