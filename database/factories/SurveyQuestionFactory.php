<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'question_id' => fake()->unique()->slug(3),
            'prompt' => fake()->sentence(),
            'description' => fake()->sentence(),
            'placeholder' => fake()->sentence(),
            'order' => 1,
        ];
    }
}
