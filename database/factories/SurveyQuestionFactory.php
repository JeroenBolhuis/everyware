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
            'question' => fake()->sentence(),
            'type' => fake()->randomElement(['radio', 'swipe', 'textarea']),
            'options' => null,
            'required' => true,
            'sort_order' => 1,
        ];
    }
}
