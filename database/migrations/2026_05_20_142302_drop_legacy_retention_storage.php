<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('survey_responses', 'delete_on_date')) {
            try {
                Schema::table('survey_responses', function (Blueprint $table): void {
                    $table->dropIndex('survey_responses_delete_on_date_index');
                });
            } catch (Throwable) {
                // Index may already be absent in some environments.
            }

            Schema::table('survey_responses', function (Blueprint $table): void {
                $table->dropColumn('delete_on_date');
            });
        }

        Schema::dropIfExists('survey_answer_retention_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('survey_answer_retention_settings')) {
            Schema::create('survey_answer_retention_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('auto_delete_after_days')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('survey_responses', 'delete_on_date')) {
            Schema::table('survey_responses', function (Blueprint $table): void {
                $table->date('delete_on_date')->nullable()->index();
            });
        }
    }
};
