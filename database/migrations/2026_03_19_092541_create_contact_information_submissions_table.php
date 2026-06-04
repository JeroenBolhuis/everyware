<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'personal';

    public function up(): void
    {
        Schema::connection('personal')->create('contact_information_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('survey_response_id')->nullable()->unique();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('survey_id');
            $table->index('survey_response_id');
        });
    }

    public function down(): void
    {
        Schema::connection('personal')->dropIfExists('contact_information_submissions');
    }
};
