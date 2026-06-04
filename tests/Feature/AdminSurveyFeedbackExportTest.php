<?php

use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function createExportableSurvey(): Survey
{
    $survey = Survey::factory()->active()->create([
        'title' => 'Docentenfeedback kwartaal 1',
        'description' => 'Feedback export test',
    ]);

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Wat ging er goed?',
        'type' => 'textarea',
        'options' => null,
        'required' => true,
        'sort_order' => 1,
    ]);

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Wat kan beter?',
        'type' => 'textarea',
        'options' => null,
        'required' => false,
        'sort_order' => 2,
    ]);

    return $survey->fresh('questions');
}

function addSurveyResponseWithAnswers(
    Survey $survey,
    array $answers,
    array $contact = [],
    ?string $submittedAt = null,
): SurveyResponse {
    $participant = Participant::factory()->withEmail($contact['email'] ?? fake()->unique()->safeEmail())->create();

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => $contact === [],
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => $submittedAt ?? now(),
    ]);

    foreach ($survey->questions as $index => $question) {
        $response->answers()->create([
            'survey_question_id' => $question->id,
            'answer' => $answers[$index] ?? null,
        ]);
    }

    return $response->fresh('answers.question', 'participant');
}

function exportSurveyFeedback(User $employee, Survey $survey, string $format): TestResponse
{
    actingAs($employee);

    return get(route('admin.surveys.export', ['survey' => $survey, 'format' => $format]));
}

function xlsxContents(TestResponse $response): array
{
    $tempFile = tempnam(sys_get_temp_dir(), 'survey-feedback-test-');
    $zipFile = $tempFile.'.zip';
    @unlink($tempFile);
    file_put_contents($zipFile, $response->getContent());

    $archive = new PharData($zipFile);
    $contents = [
        'sheet' => $archive['xl/worksheets/sheet1.xml']->getContent(),
        'workbook' => $archive['xl/workbook.xml']->getContent(),
        'content_types' => $archive['[Content_Types].xml']->getContent(),
        'workbook_rels' => $archive['xl/_rels/workbook.xml.rels']->getContent(),
    ];

    @unlink($zipFile);

    return $contents;
}

it('exports all feedback for a survey as an xlsx file', function () {
    $employee = User::factory()->admin()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers(
        $survey,
        [
            'De docent legde de stof helder uit.',
            'Meer praktijkvoorbeelden toevoegen.',
        ],
        [
            'name' => 'Sam Student',
            'email' => 'sam@example.com',
            'phone' => '+31611111111',
        ],
        '2026-05-01 10:00:00',
    );

    addSurveyResponseWithAnswers(
        $survey,
        [
            'Fijn tempo tijdens de les.',
            '-',
        ],
        submittedAt: '2026-05-02 14:30:00',
    );

    $response = exportSurveyFeedback($employee, $survey, 'xlsx');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment; filename="survey-feedback-docentenfeedback-kwartaal-1.xlsx"');

    $xlsx = xlsxContents($response);

    expect($xlsx['workbook'])
        ->toContain('Docentenfeedback kwartaal 1');

    expect($xlsx['sheet'])
        ->toContain('<autoFilter ref="A1:F3"/>')
        ->toContain('Inzending ID')
        ->toContain('Wat ging er goed?')
        ->toContain('Wat kan beter?')
        ->toContain('sam@example.com')
        ->toContain('De docent legde de stof helder uit.')
        ->toContain('Meer praktijkvoorbeelden toevoegen.')
        ->toContain('Fijn tempo tijdens de les.');

    expect($xlsx['content_types'])
        ->toContain('/xl/workbook.xml')
        ->toContain('/xl/worksheets/sheet1.xml');

    expect($xlsx['workbook_rels'])
        ->toContain('Target="worksheets/sheet1.xml"')
        ->toContain('Target="styles.xml"');
});

it('exports all feedback for a survey as a csv file', function () {
    $employee = User::factory()->admin()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers(
        $survey,
        ['Sterke uitleg', 'Meer voorbeelden'],
        ['name' => 'Alex', 'email' => 'alex@example.com'],
    );

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment; filename="survey-feedback-docentenfeedback-kwartaal-1.csv"');

    $content = $response->getContent();

    expect($content)
        ->toContain('Inzending ID')
        ->toContain('Wat ging er goed?')
        ->toContain('Wat kan beter?')
        ->toContain('alex@example.com')
        ->toContain('Sterke uitleg')
        ->toContain('Meer voorbeelden');
});

it('preserves answer values like 0 in exports', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers($survey, ['0', '1']);

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('0')
        ->not->toContain(';-;"1"');
});

it('does not expose participant email for anonymous responses in exports', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers(
        $survey,
        ['Sterke uitleg', 'Meer voorbeelden'],
    );

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Contactgegevens')
        ->toContain('Sterke uitleg')
        ->not->toContain('alex@example.com');
});

it('does not expose participant email to lic employees in exports', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers(
        $survey,
        ['Sterke uitleg', 'Meer voorbeelden'],
        ['email' => 'alex@example.com'],
    );

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Contactgegevens')
        ->toContain('Niet anoniem')
        ->toContain('Sterke uitleg')
        ->not->toContain('E-mail')
        ->not->toContain('alex@example.com');
});

it('excludes blocked participant responses from exports', function () {
    $employee = User::factory()->admin()->createOne();
    $survey = createExportableSurvey();

    addSurveyResponseWithAnswers($survey, ['Zichtbaar antwoord', '']);

    $blockedParticipant = Participant::factory()->create(['blocked_at' => now()]);
    $blockedResponse = addSurveyResponseWithAnswers($survey, ['Geblokkeerd antwoord', '']);
    $blockedResponse->update(['participant_id' => $blockedParticipant->id]);

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Zichtbaar antwoord')
        ->not->toContain('Geblokkeerd antwoord');
});

it('returns 404 for unsupported export formats', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createExportableSurvey();

    exportSurveyFeedback($employee, $survey, 'pdf')
        ->assertNotFound();
});

it('falls back to a safe file name when the survey title has no slug', function () {
    $employee = User::factory()->licEmployee()->createOne();
    $survey = createExportableSurvey();
    $survey->update(['title' => '???']);

    $response = exportSurveyFeedback($employee, $survey, 'csv');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment; filename="survey-feedback-export.csv"');
});
