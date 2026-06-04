<?php

namespace Database\Seeders;

use App\Models\ContactInformationSubmission;
use App\Models\SurveyResponse;
use Illuminate\Database\Seeder;

class ContactInformationSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        SurveyResponse::query()
            ->latest('id')
            ->take(5)
            ->get()
            ->each(function (SurveyResponse $response): void {
                $response->update(['is_anonymous' => false]);

                ContactInformationSubmission::firstOrCreate(
                    ['survey_response_id' => $response->id],
                    [
                        'survey_id' => $response->survey_id,
                        'name' => fake()->name(),
                        'email' => fake()->unique()->safeEmail(),
                        'phone' => fake()->phoneNumber(),
                        'note' => fake()->optional()->sentence(),
                    ]
                );
            });
    }
}
