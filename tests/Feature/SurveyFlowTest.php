<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Reset database between tests
uses(RefreshDatabase::class);

/* Create a test survey with 2 questions */
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

/* Create a survey response manually */
function createSurveyResponse(?Survey $survey = null, array $attributes = []): SurveyResponse
{
    $survey ??= createSurvey();

    return SurveyResponse::create(array_merge([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ], $attributes));
}

/* Survey page loads when active */
it('can open the survey page', function () {
    $survey = createSurvey(['is_active' => true]);

    $response = $this->get('/survey/' . $survey->id);

    $response->assertOk();
    $response->assertSee('Are you satisfied?');
});

/* Inactive survey returns 404 */
it('returns 404 for an inactive survey page', function () {
    $survey = createSurvey(['is_active' => false]);

    $response = $this->get('/survey/' . $survey->id);

    $response->assertNotFound();
});

/* Survey can be submitted */
it('can submit a survey', function () {
    $survey = createSurvey();
    $question1 = $survey->questions[0];
    $question2 = $survey->questions[1];

    $response = $this->post('/survey/' . $survey->id, [
        'answers' => [
            $question1->id => 'yes',
            $question2->id => 'Because it works',
        ],
    ]);

    $surveyResponse = SurveyResponse::latest()->first();

    $response->assertRedirect(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $this->assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
    ]);
});

/* Required questions must be answered */
it('requires an answer for required questions', function () {
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

/* Contact details can be stored and hashed */
it('can save contact details after submission', function () {
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

/* Empty contact form should not store data */
it('skips saving contact details when all fields are empty', function () {
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

/* Thank-you page shows shared contact fields */
it('shows shared contact details on the thank you page', function () {
    $surveyResponse = createSurveyResponse();

    ContactInformationSubmission::create([
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
        'name' => Hash::make('Ali Test'),
        'email' => Hash::make('ali@example.com'),
        'phone' => null,
    ]);

    $response = $this->get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Je hebt contactgegevens gedeeld');
});

/* Thank-you page shows form if no contact data */
it('shows the contact form on the thank you page when no contact details were shared', function () {
    $surveyResponse = createSurveyResponse();

    $response = $this->get(route('survey.thankyou', ['response' => $surveyResponse->id]));

    $response->assertOk();
    $response->assertSee('Contactgegevens opslaan');
});

/* Withdrawal page opens with valid token */
it('can open the withdrawal page with a valid token', function () {
    $surveyResponse = createSurveyResponse(null, [
        'withdrawal_token' => 'test-token-123',
    ]);

    $response = $this->get('/survey-withdraw/' . $surveyResponse->withdrawal_token);

    $response->assertOk();
});

/* Withdrawal removes contact info and marks response */
it('removes contact details and marks the response as withdrawn', function () {
    $surveyResponse = createSurveyResponse(null, [
        'withdrawal_token' => 'test-token-123',
        'withdrawn_at' => null,
    ]);

    ContactInformationSubmission::create([
        'survey_id' => $surveyResponse->survey_id,
        'survey_response_id' => $surveyResponse->id,
        'name' => Hash::make('Ali Test'),
        'email' => Hash::make('ali@example.com'),
        'phone' => Hash::make('0612345678'),
    ]);

    $response = $this->post('/survey-withdraw/' . $surveyResponse->withdrawal_token);

    $response->assertOk();

    // Response still exists but is marked withdrawn
    expect($surveyResponse->fresh()->withdrawn_at)->not->toBeNull();

    // Contact data must be removed
    $this->assertDatabaseMissing('contact_information_submissions', [
        'survey_response_id' => $surveyResponse->id,
    ]);
});