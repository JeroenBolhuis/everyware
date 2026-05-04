<?php

namespace App\Actions\Surveys;

use App\Models\Survey;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BuildSurveyFeedbackExport
{
    private const EMPTY_VALUE = '-';

    private const FORMATS = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv; charset=UTF-8',
    ];

    private const BASE_HEADERS = [
        'Inzending ID',
        'Ingestuurd op',
        'Status',
        'Naam',
        'E-mail',
        'Telefoon',
    ];

    private const BASE_WIDTHS = [70, 110, 90, 130, 180, 120];

    public function __construct(
        private readonly BuildSurveyFeedbackWorkbook $buildSurveyFeedbackWorkbook,
    ) {
    }

    public function build(Survey $survey, string $format = 'xlsx'): string
    {
        $data = $this->data($survey);

        return $format === 'csv' ? $this->csv($data) : $this->buildSurveyFeedbackWorkbook->build($data);
    }

    public function contentType(string $format = 'xlsx'): string
    {
        return self::FORMATS[$format] ?? self::FORMATS['xlsx'];
    }

    public function fileName(Survey $survey, string $format = 'xlsx'): string
    {
        $slug = Str::slug($survey->title);

        return 'survey-feedback-'.($slug !== '' ? $slug : 'export').'.'.$format;
    }

    public function supports(string $format): bool
    {
        return isset(self::FORMATS[$format]);
    }

    private function data(Survey $survey): array
    {
        $survey->loadMissing([
            'questions',
            'responses.answers',
            'responses.contactInformationSubmission',
        ]);

        $questions = $survey->questions->sortBy('sort_order')->values();

        return [
            'sheet' => $this->buildSurveyFeedbackWorkbook->sheetName($survey->title),
            'headers' => [...self::BASE_HEADERS, ...$questions->pluck('question')->all()],
            'widths' => [...self::BASE_WIDTHS, ...array_fill(0, $questions->count(), 260)],
            'rows' => $this->rows($survey, $questions),
        ];
    }

    private function rows(Survey $survey, Collection $questions): array
    {
        return $survey->responses
            ->sortByDesc(fn ($response) => $response->submitted_at?->getTimestamp() ?? 0)
            ->map(function ($response) use ($questions) {
                $answers = $response->answers->pluck('answer', 'survey_question_id');
                $contact = $response->contactInformationSubmission;

                return [
                    (string) $response->id,
                    $response->submitted_at?->format('d-m-Y H:i') ?? self::EMPTY_VALUE,
                    $response->withdrawn_at ? 'Ingetrokken' : 'Actief',
                    $this->cellValue($contact?->name),
                    $this->cellValue($contact?->email),
                    $this->cellValue($contact?->phone),
                    ...$questions->map(fn ($question) => $this->cellValue($answers->get($question->id)))->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function cellValue(mixed $value): string
    {
        return (string) ($value === null || $value === '' ? self::EMPTY_VALUE : $value);
    }

    private function csv(array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Kon het CSV-bestand niet opbouwen.');
        }

        foreach ([$data['headers'], ...$data['rows']] as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".($content ?: '');
    }
}
