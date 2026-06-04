<?php

use App\Actions\Surveys\BuildSurveyFeedbackExport;
use App\Actions\Surveys\BuildSurveyFeedbackWorkbook;
use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Services\ParticipantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reports supported formats and generated file names', function () {
    $export = new BuildSurveyFeedbackExport(new BuildSurveyFeedbackWorkbook, app(ParticipantService::class));
    $survey = new Survey(['title' => 'LIC feedback ronde']);

    expect($export->supports('xlsx'))->toBeTrue()
        ->and($export->supports('csv'))->toBeTrue()
        ->and($export->supports('pdf'))->toBeFalse()
        ->and($export->contentType('xlsx'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($export->contentType('csv'))->toBe('text/csv; charset=UTF-8')
        ->and($export->contentType('unknown'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($export->fileName($survey, 'csv'))->toBe('survey-feedback-lic-feedback-ronde.csv')
        ->and($export->fileName(new Survey(['title' => '???']), 'xlsx'))->toBe('survey-feedback-export.xlsx');
});

it('builds csv exports with personal data and visible responses only', function () {
    $export = new BuildSurveyFeedbackExport(new BuildSurveyFeedbackWorkbook, app(ParticipantService::class));
    $survey = Survey::factory()->createOne(['title' => 'Tevredenheid']);
    $firstQuestion = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'question' => 'Hoe ging het?',
        'sort_order' => 2,
    ]);
    $secondQuestion = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'question' => 'Nog opmerkingen?',
        'sort_order' => 1,
    ]);
    $participant = Participant::factory()->withEmail('student@example.com')->createOne();
    $blockedParticipant = Participant::factory()->withEmail('blocked@example.com')->createOne([
        'blocked_at' => now(),
    ]);
    $visibleResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $participant->id,
        'is_anonymous' => false,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ]);
    $blockedResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => $blockedParticipant->id,
        'is_anonymous' => false,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => now(),
    ]);

    SurveyAnswer::create([
        'survey_response_id' => $visibleResponse->id,
        'survey_question_id' => $firstQuestion->id,
        'answer' => 'Goed',
    ]);
    SurveyAnswer::create([
        'survey_response_id' => $visibleResponse->id,
        'survey_question_id' => $secondQuestion->id,
        'answer' => '',
    ]);
    SurveyAnswer::create([
        'survey_response_id' => $blockedResponse->id,
        'survey_question_id' => $firstQuestion->id,
        'answer' => 'Verborgen',
    ]);

    $csv = $export->build($survey, 'csv');

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('"Inzending ID";')
        ->and($csv)->toContain('"Nog opmerkingen?";"Hoe ging het?"')
        ->and($csv)->toContain('student@example.com')
        ->and($csv)->toContain('-;Goed')
        ->and($csv)->not->toContain('blocked@example.com')
        ->and($csv)->not->toContain('Verborgen');
});

it('builds anonymized csv exports', function () {
    $export = new BuildSurveyFeedbackExport(new BuildSurveyFeedbackWorkbook, app(ParticipantService::class));
    $survey = Survey::factory()->createOne();
    SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'question' => 'Vraag',
    ]);
    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'participant_id' => Participant::factory()->withEmail('student@example.com')->createOne()->id,
        'is_anonymous' => true,
        'withdrawal_token' => (string) Str::uuid(),
        'submitted_at' => null,
        'withdrawn_at' => now(),
    ]);

    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $survey->questions()->first()->id,
        'answer' => null,
    ]);

    $csv = $export->build($survey, 'csv', includePersonalData: false);

    expect($csv)->toContain('Contactgegevens')
        ->and($csv)->toContain('Ingetrokken;Anoniem;-')
        ->and($csv)->not->toContain('student@example.com');
});

it('builds workbook output and sanitizes worksheet names', function () {
    $workbook = new BuildSurveyFeedbackWorkbook;

    $xlsx = $workbook->build([
        'sheet' => $workbook->sheetName('Feedback: [ronde] / voorjaar * 2026? met lange titel'),
        'headers' => ['Kolom <een>', 'Kolom twee'],
        'widths' => [70, 260],
        'rows' => [
            ['Waarde & een', "Regel\nTwee"],
        ],
    ]);

    expect($workbook->sheetName('Feedback: [ronde] / voorjaar * 2026? met lange titel'))->toBe('Feedback   ronde    voorjaar')
        ->and($workbook->sheetName(''))->toBe('Feedback export')
        ->and($xlsx)->toStartWith('PK')
        ->and($xlsx)->toContain('[Content_Types].xml')
        ->and($xlsx)->toContain('xl/workbook.xml')
        ->and($xlsx)->toContain('Kolom &lt;een&gt;')
        ->and($xlsx)->toContain('Waarde &amp; een');
});
