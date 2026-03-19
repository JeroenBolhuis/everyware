<?php

namespace Database\Factories;

use App\Models\Enquete;
use App\Models\EnqueteSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnqueteSubmission>
 */
class EnqueteSubmissionFactory extends Factory
{
    protected $model = EnqueteSubmission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enquete_id' => Enquete::factory(),
            'submitted_at' => now(),
            'respondent_key' => Str::uuid()->toString(),
        ];
    }
}
