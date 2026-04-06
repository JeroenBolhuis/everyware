<?php

use App\Mail\SurveySubmissionConfirmationMail;
use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function createSurvey(array $attributes = []): Survey
{
    $survey = Survey::factory()->create(array_merge([
        'title' => 'Test Survey',
        'description' => 'Test description',
        'is_active' => true,
    ], $attributes));

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Are you satisfied?',
        'type' => 'radio',
        'required' => true,
        'options' => ['yes', 'no'],
        'sort_order' => 1,
    ]);

    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'question' => 'Why?',
        'type' => 'textarea',
        'required' => false,
        'options' => null,
        'sort_order' => 2,
    ]);

    return $survey->fresh('questions');
}

function createSurveyResponse(?Survey $survey = null, array $attributes = []): SurveyResponse
{
    $survey ??= createSurvey();

    return SurveyResponse::create(array_merge([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ], $attributes));
}

it('opens the survey page', function () {
    $survey = createSurvey(['is_active' => true]);

    $this->get('/survey/' . $survey->id)
        ->assertOk()
        ->assertSee('Are you satisfied?')
        ->assertSee('Laat optioneel je naam en e-mailadres achter voor een bevestigingsmail');
});

it('returns 404 for inactive survey', function () {
    $survey = createSurvey(['is_active' => false]);

    $this->get('/survey/' . $survey->id)->assertNotFound();
});

it('submits a survey and sends a confirmation email when an email address is provided', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];
    $question2 = $survey->questions[1];

    $response = $this->followingRedirects()->post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
            $question2->id => 'Because it works',
        ],
        'contact_name' => 'Ali Test',
        'contact_email' => 'Ali@Example.com',
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertOk()
        ->assertSee('Er is een bevestigingsmail verstuurd')
        ->assertSee('a*i@example.com');

    $this->assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
        'student_name' => 'Ali Test',
        'student_email' => 'ali@example.com',
    ]);

    Mail::assertSent(SurveySubmissionConfirmationMail::class, function (SurveySubmissionConfirmationMail $mail) use ($surveyResponse) {
        return $mail->response->is($surveyResponse)
            && $mail->hasTo('ali@example.com');
    });
});

it('submits a survey without sending an email when no email address is provided', function () {
    Mail::fake();

    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = $this->post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
        'contact_name' => '',
        'contact_email' => '',
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $this->assertDatabaseHas('survey_responses', [
        'id' => $surveyResponse->id,
        'student_name' => null,
        'student_email' => null,
    ]);

    Mail::assertNothingSent();
});

it('requires answers for required questions', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $response = $this->from('/survey/' . $survey->id)
        ->post('/survey/' . $survey->id, [
            'answers' => [
                $question1->id => '',
            ],
        ]);

    $response->assertRedirect('/survey/' . $survey->id);
    $response->assertSessionHasErrors([
        "answers.{$question1->id}",
    ]);
});

it('saves legacy contact details after submission for compatibility', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];

    $this->post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response = $this->post('/survey/response/' . $surveyResponse->id . '/contact-details', [
        'contact_name' => 'Ali Test',
        'contact_email' => 'Ali@Example.com',
        'contact_phone' => '06 12345678',
    ]);

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $this->assertDatabaseHas('contact_information_submissions', [
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
    ]);

    $contactSubmission = ContactInformationSubmission::where('survey_response_id', $surveyResponse->id)->first();

    expect(Hash::check('Ali Test', $contactSubmission->name))->toBeTrue();
    expect(Hash::check('ali@example.com', $contactSubmission->email))->toBeTrue();
    expect(Hash::check('0612345678', $contactSubmission->phone))->toBeTrue();
});

it('skips saving legacy contact details when all fields are empty', function () {
    $surveyResponse = createSurveyResponse();

    $response = $this->post('/survey/response/' . $surveyResponse->id . '/contact-details', [
        'contact_name' => '',
        'contact_email' => '',
        'contact_phone' => '',
    ]);

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $this->assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);
});

it('shows the mail confirmation state on the thank you page', function () {
    $surveyResponse = createSurveyResponse(null, [
        'student_name' => 'Ali Test',
        'student_email' => 'ali@example.com',
    ]);

    $this->withSession(['confirmationMailStatus' => 'sent'])
        ->get(route('survey.thankyou', ['response' => $surveyResponse->id]))
        ->assertOk()
        ->assertSee('Er is een bevestigingsmail verstuurd')
        ->assertSee('Naam opgeslagen')
        ->assertSee('E-mailadres opgeslagen');
});
