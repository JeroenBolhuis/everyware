<?php

use App\Models\Survey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->uuid('share_token')->nullable()->unique()->after('is_active');
        });

        Survey::query()->each(function (Survey $survey): void {
            $survey->updateQuietly(['share_token' => Str::uuid()]);
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->uuid('share_token')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
