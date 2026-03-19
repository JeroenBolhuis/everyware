<?php

namespace Database\Factories;

use App\Models\EnqueteAnswer;
use App\Models\EnqueteQuestion;
use App\Models\EnqueteSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnqueteAnswer>
 */
class EnqueteAnswerFactory extends Factory
{
    protected $model = EnqueteAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => EnqueteSubmission::factory(),
            'question_id' => EnqueteQuestion::factory(),
            'value' => [
                'text' => fake()->sentence(),
            ],
        ];
    }
}
