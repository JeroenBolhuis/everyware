<?php

namespace App\Console\Commands;

use App\Actions\Surveys\DeleteSurveySubmission;
use App\Models\SurveyResponse;
use Illuminate\Console\Command;

class PruneSurveyAnswers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-survey-answers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete survey responses whose delete date has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleteSurveySubmission = app(DeleteSurveySubmission::class);

        $deletedCount = 0;

        SurveyResponse::query()
            ->whereDate('delete_on_date', '<=', now()->toDateString())
            ->chunkById(200, function ($responses) use ($deleteSurveySubmission, &$deletedCount): void {
                foreach ($responses as $response) {
                    $deleteSurveySubmission->handle($response);
                    $deletedCount++;
                }
            });

        $this->info("Deleted {$deletedCount} survey responses with an expired delete date.");

        return self::SUCCESS;
    }
}
