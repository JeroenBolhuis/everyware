<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete()->unique();
            $table->uuid('pseudonym_uuid')->unique();
            $table->text('full_name_encrypted')->nullable();
            $table->text('email_encrypted')->nullable();
            $table->string('email_hash', 64)->nullable()->index();
            $table->string('consent_source')->default('survey_submit');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_recipients');
    }
};
