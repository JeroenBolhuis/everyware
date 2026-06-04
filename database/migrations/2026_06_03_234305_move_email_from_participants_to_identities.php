<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Privacy refactor:
 * - Copies participant emails from the feedback DB into participant_identities on the personal DB.
 * - Drops the email column from participants (feedback DB).
 * - Adds a non-PII `academy` column derived from the email domain.
 *
 * After this migration the feedback database contains zero email addresses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Derive and store academy (non-PII) for each participant, then copy email to personal DB.
        $now = now();

        DB::table('participants')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now) {
                foreach ($rows as $row) {
                    $email = $row->email ?? null;

                    if ($email === null) {
                        continue;
                    }

                    $academy = $this->academyFromEmail($email);

                    // Store derived non-PII academy on the feedback DB.
                    DB::table('participants')->where('id', $row->id)->update([
                        'academy' => $academy,
                        'updated_at' => $now,
                    ]);

                    // Move email to the personal DB (skip if already migrated).
                    $exists = DB::connection('personal')
                        ->table('participant_identities')
                        ->where('participant_id', $row->id)
                        ->exists();

                    if (! $exists) {
                        DB::connection('personal')->table('participant_identities')->insert([
                            'participant_id' => $row->id,
                            'email' => Str::lower(trim($email)),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });

        // 2. Add academy column to feedback DB participants table.
        if (! Schema::hasColumn('participants', 'academy')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('academy')->nullable()->after('blocked_at')
                    ->comment('Non-PII academy derived from email domain. Set once at registration.');
            });
        }

        // 3. Drop email from feedback DB — this is the privacy boundary.
        if (Schema::hasColumn('participants', 'email')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore email column on feedback DB.
        if (! Schema::hasColumn('participants', 'email')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->string('email')->nullable()->after('id');
            });
        }

        // Copy emails back from personal DB.
        DB::connection('personal')
            ->table('participant_identities')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('participants')->where('id', $row->participant_id)->update([
                        'email' => $row->email,
                    ]);
                }
            });

        // Add unique constraint back.
        Schema::table('participants', function (Blueprint $table) {
            $table->unique('email');
        });

        // Drop academy column.
        if (Schema::hasColumn('participants', 'academy')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->dropColumn('academy');
            });
        }
    }

    private function academyFromEmail(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return null;
        }

        $domain = Str::of($email)->afterLast('@')->lower()->toString();

        $map = [
            'avans' => ['avans.nl'],
            'fontys' => ['fontys.nl'],
            'hogeschool-utrecht' => ['hu.nl', 'student.hu.nl'],
            'hogeschool-rotterdam' => ['hr.nl', 'student.hr.nl'],
            'inholland' => ['inholland.nl', 'student.inholland.nl'],
        ];

        foreach ($map as $academy => $domains) {
            foreach ($domains as $allowed) {
                if ($domain === $allowed || Str::endsWith($domain, '.'.$allowed)) {
                    return $academy;
                }
            }
        }

        return null;
    }
};
