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
        Schema::create('enquete_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquete_id')->constrained('enquetes')->cascadeOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->string('respondent_key')->nullable();
            $table->timestamps();

            $table->index(['enquete_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquete_submissions');
    }
};
