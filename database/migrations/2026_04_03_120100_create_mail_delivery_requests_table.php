<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_recipient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->uuid('pseudonym_uuid')->index();
            $table->string('mail_template')->default('survey_submission_confirmation');
            $table->string('mail_status')->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('mail_requested_at')->nullable();
            $table->timestamp('mail_sent_at')->nullable();
            $table->timestamp('mail_failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_delivery_requests');
    }
};
