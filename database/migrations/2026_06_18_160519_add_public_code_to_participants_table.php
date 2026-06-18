<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('public_code', 8)->nullable()->after('id');
        });

        DB::table('participants')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(500, function ($participants): void {
                foreach ($participants as $participant) {
                    DB::table('participants')
                        ->where('id', $participant->id)
                        ->update(['public_code' => $this->uniquePublicCode()]);
                }
            });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('public_code', 8)->nullable(false)->change();
            $table->unique('public_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['public_code']);
            $table->dropColumn('public_code');
        });
    }

    private function uniquePublicCode(): string
    {
        do {
            $code = (string) random_int(10_000_000, 99_999_999);
        } while (DB::table('participants')->where('public_code', $code)->exists());

        return $code;
    }
};
