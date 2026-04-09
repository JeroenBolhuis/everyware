<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surveys', 'share_token')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->uuid('share_token')->nullable()->unique()->after('is_active');
            });
        }

        DB::table('surveys')->whereNull('share_token')->get()->each(function ($survey) {
            DB::table('surveys')
                ->where('id', $survey->id)
                ->update(['share_token' => Str::uuid()->toString()]);
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
