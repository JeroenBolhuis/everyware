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
        Schema::create('survey_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('retention_years')->default(5);
            $table->unsignedSmallInteger('upcoming_warning_days')->default(7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_settings');
    }
};
