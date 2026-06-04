<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SurveyAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Survey::query()
            ->with('questions')
            ->get()
            ->each(function (Survey $survey): void {
                if ($survey->questions->isEmpty()) {
                    return;
                }

                $responseCount = fake()->numberBetween(2, 4);

                for ($_index = 0; $_index < $responseCount; $_index++) {
                    $submittedAt = now()->subDays(fake()->numberBetween(1, 45));

                    $response = SurveyResponse::create([
                        'survey_id' => $survey->id,
                        'participant_id' => Participant::factory()->create()->id,
                        'withdrawal_token' => (string) Str::uuid(),
                        'submitted_at' => $submittedAt,
                    ]);

                    $survey->questions->each(function (SurveyQuestion $question) use ($response): void {
                        SurveyAnswer::create([
                            'survey_response_id' => $response->id,
                            'survey_question_id' => $question->id,
                            'answer' => $this->seededAnswerForQuestion($question),
                        ]);
                    });
                }
            });
    }

    private function seededAnswerForQuestion(SurveyQuestion $question): string
    {
        if ($question->type === 'textarea') {
            return fake()->sentence();
        }

        $options = is_array($question->options) ? $question->options : [];

        if ($options !== []) {
            return (string) fake()->randomElement($options);
        }

        return 'ja';
    }
}
