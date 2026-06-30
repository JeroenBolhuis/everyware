<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SurveySeeder::class,
            SurveyAnswerSeeder::class,
            ContactInformationSubmissionSeeder::class,
        ]);

        User::query()->updateOrCreate(
            ['email' => 'p.groep@everyware.nl'],
            [
                'name' => 'Admin',
                'role' => Role::Admin,
                'password' => Hash::make('QSpx34P32Jt!pfZ'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'lic.medewerker@everyware.nl'],
            [
                'name' => 'LIC Medewerker',
                'role' => Role::LicEmployee,
                'password' => Hash::make('QSpx34P32Jt!pfZ'),
            ]
        );
    }
}
