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
        $orphanedResponseIds = DB::table('survey_responses')
            ->whereNull('participant_id')
            ->pluck('id');

        if ($orphanedResponseIds->isNotEmpty()) {
            DB::table('contact_information_submissions')
                ->whereIn('survey_response_id', $orphanedResponseIds)
                ->delete();

            DB::table('survey_answers')
                ->whereIn('survey_response_id', $orphanedResponseIds)
                ->delete();

            DB::table('participant_points_history')
                ->where('source_type', 'App\\Models\\SurveyResponse')
                ->whereIn('source_id', $orphanedResponseIds)
                ->delete();

            DB::table('survey_responses')
                ->whereIn('id', $orphanedResponseIds)
                ->delete();
        }

        DB::table('contact_information_submissions')->delete();

        if (! Schema::hasColumn('survey_responses', 'is_anonymous')) {
            Schema::table('survey_responses', function (Blueprint $table) {
                $table->boolean('is_anonymous')->default(true)->after('participant_id');
            });
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('participant_id')->nullable(false)->change();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
        });

        if (Schema::hasColumn('participants', 'name')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('participants', 'name')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('name')->nullable()->after('email');
            });
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('participant_id')->nullable()->change();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('participant_id')->references('id')->on('participants')->nullOnDelete();
        });

        if (Schema::hasColumn('survey_responses', 'is_anonymous')) {
            Schema::table('survey_responses', function (Blueprint $table) {
                $table->dropColumn('is_anonymous');
            });
        }
    }
};
