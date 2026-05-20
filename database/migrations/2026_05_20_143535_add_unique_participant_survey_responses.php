<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateResponseIds = DB::table('survey_responses')
            ->select('id')
            ->whereIn('id', function ($query): void {
                $query
                    ->selectRaw('id')
                    ->fromRaw('(
                        SELECT id,
                            ROW_NUMBER() OVER (
                                PARTITION BY survey_id, participant_id
                                ORDER BY submitted_at ASC, id ASC
                            ) AS duplicate_position
                        FROM survey_responses
                    ) AS ranked_survey_responses')
                    ->where('duplicate_position', '>', 1);
            })
            ->pluck('id');

        if ($duplicateResponseIds->isNotEmpty()) {
            DB::table('contact_information_submissions')
                ->whereIn('survey_response_id', $duplicateResponseIds)
                ->delete();

            DB::table('survey_answers')
                ->whereIn('survey_response_id', $duplicateResponseIds)
                ->delete();

            DB::table('participant_points_history')
                ->where('source_type', 'App\\Models\\SurveyResponse')
                ->whereIn('source_id', $duplicateResponseIds)
                ->delete();

            DB::table('survey_responses')
                ->whereIn('id', $duplicateResponseIds)
                ->delete();
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique(['survey_id', 'participant_id'], 'survey_responses_survey_participant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique('survey_responses_survey_participant_unique');
        });
    }
};
