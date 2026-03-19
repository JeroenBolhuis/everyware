<?php

namespace Database\Seeders;

use App\Models\Enquete;
use App\Models\EnqueteAnswer;
use App\Models\EnqueteQuestion;
use App\Models\EnqueteSubmission;
use Illuminate\Database\Seeder;

class EnqueteSeeder extends Seeder
{
    public function run(): void
    {
        // Create 3 draft enquetes with questions, but no submissions
        Enquete::factory()
            ->count(3)
            ->has(
                EnqueteQuestion::factory()->count(4),
                'questions'
            )
            ->create();

        // Create 5 published enquetes, each with questions and submissions with answers
        Enquete::factory()
            ->published()
            ->count(5)
            ->has(
                EnqueteQuestion::factory()->count(5)->sequence(
                    fn ($sequence) => ['sort_order' => $sequence->index]
                ),
                'questions'
            )
            ->create()
            ->each(function (Enquete $enquete) {
                $questions = $enquete->questions;

                // Create 10 submissions per published enquete
                EnqueteSubmission::factory()
                    ->count(10)
                    ->for($enquete)
                    ->create()
                    ->each(function (EnqueteSubmission $submission) use ($questions) {
                        // Create one answer per question per submission
                        $questions->each(function (EnqueteQuestion $question) use ($submission) {
                            EnqueteAnswer::factory()->create([
                                'submission_id' => $submission->id,
                                'question_id'   => $question->id,
                                'value'         => self::fakeValueForType($question->type),
                            ]);
                        });
                    });
            });
    }

    /**
     * Generate a realistic fake value payload based on the question type.
     */
    private static function fakeValueForType(string $type): array
    {
        return match ($type) {
            'text'     => ['text' => fake()->sentence()],
            'textarea' => ['text' => fake()->paragraph()],
            'radio'    => ['selected' => fake()->randomElement(['Option A', 'Option B', 'Option C'])],
            'checkbox' => ['selected' => fake()->randomElements(['Option A', 'Option B', 'Option C', 'Option D'], fake()->numberBetween(1, 3))],
            'scale'    => ['value' => fake()->numberBetween(1, 10)],
            default    => ['text' => fake()->word()],
        };
    }
}