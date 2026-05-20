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
    protected $description = 'Delete survey responses older than configured retention years';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleteSurveySubmission = app(DeleteSurveySubmission::class);
        $cutoff = now()->subYears((int) config('surveys.retention_years'));

        $deletedCount = 0;

        SurveyResponse::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where('submitted_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('submitted_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
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
