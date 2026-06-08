<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the participant_identities table on the **personal** database connection.
 *
 * This is the only table in the application that stores email addresses (PII).
 * In production it lives on a separate database server with restricted credentials.
 */
return new class extends Migration
{
    protected $connection = 'personal';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('personal')->hasTable('participant_identities')) {
            return;
        }

        Schema::connection('personal')->create('participant_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('participant_id')->unique();
            $table->string('email')->unique();
            $table->timestamps();

            $table->index('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('personal')->dropIfExists('participant_identities');
    }
};
