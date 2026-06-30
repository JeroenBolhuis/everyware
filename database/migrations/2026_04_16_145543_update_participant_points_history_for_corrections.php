<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_points_history', function (Blueprint $table) {
            $table->string('source_type')->nullable()->change();
            $table->unsignedBigInteger('source_id')->nullable()->change();
            $table->string('reason')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('participant_points_history', function (Blueprint $table) {
            $table->dropColumn('reason');
            $table->string('source_type')->nullable(false)->change();
            $table->unsignedBigInteger('source_id')->nullable(false)->change();
        });
    }
};
