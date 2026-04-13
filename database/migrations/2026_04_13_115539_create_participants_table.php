<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('current_points')->default(0);
            $table->timestamps();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
        });

        $this->migrateLegacyStudentFieldsToParticipants();

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn(['student_name', 'student_email']);
        });
    }

    /**
     * Copy student_email / student_name into participants and link survey_responses.
     */
    private function migrateLegacyStudentFieldsToParticipants(): void
    {
        $now = now();

        DB::table('survey_responses')
            ->orderBy('id')
            ->select(['id', 'student_name', 'student_email'])
            ->chunkById(500, function ($rows) use ($now) {
                foreach ($rows as $row) {
                    $email = $this->normalizeEmail($row->student_email ?? null);
                    if ($email === null) {
                        continue;
                    }

                    $name = $this->normalizeName($row->student_name ?? null);

                    $participantId = DB::table('participants')->where('email', $email)->value('id');

                    if ($participantId === null) {
                        $participantId = DB::table('participants')->insertGetId([
                            'email' => $email,
                            'name' => $name,
                            'current_points' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } elseif ($name !== null) {
                        $existingName = DB::table('participants')->where('id', $participantId)->value('name');
                        if ($existingName === null || $existingName === '') {
                            DB::table('participants')->where('id', $participantId)->update([
                                'name' => $name,
                                'updated_at' => $now,
                            ]);
                        }
                    }

                    DB::table('survey_responses')->where('id', $row->id)->update([
                        'participant_id' => $participantId,
                    ]);
                }
            });
    }

    private function normalizeEmail(?string $value): ?string
    {
        $trimmed = $value !== null ? trim($value) : '';

        if ($trimmed === '') {
            return null;
        }

        return Str::lower($trimmed);
    }

    private function normalizeName(?string $value): ?string
    {
        $trimmed = $value !== null ? trim($value) : '';

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('participant_id');
            $table->string('student_name')->nullable();
            $table->string('student_email')->nullable();
        });

        Schema::dropIfExists('participants');
    }
};
