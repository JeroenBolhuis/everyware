<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $password = Hash::make('QSpx34P32Jt!pfZ');

        $updated = DB::table('users')
            ->where('email', 'admin@everyware.nl')
            ->update([
                'email' => 'p.groep@everyware.nl',
                'password' => $password,
            ]);

        if ($updated === 0) {
            DB::table('users')
                ->where('email', 'p.groep@everyware.nl')
                ->update([
                    'password' => $password,
                ]);
        }
    }

    public function down(): void
    {
        $password = Hash::make('password');

        $updated = DB::table('users')
            ->where('email', 'p.groep@everyware.nl')
            ->update([
                'email' => 'admin@everyware.nl',
                'password' => $password,
            ]);

        if ($updated === 0) {
            DB::table('users')
                ->where('email', 'admin@everyware.nl')
                ->update([
                    'password' => $password,
                ]);
        }
    }
};
