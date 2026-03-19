<?php

namespace Database\Factories;

use App\Models\Enquete;
use App\Models\EnqueteQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnqueteQuestion>
 */
class EnqueteQuestionFactory extends Factory
{
    protected $model = EnqueteQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enquete_id' => Enquete::factory(),
            'label' => fake()->sentence(),
            'type' => fake()->randomElement(['text', 'textarea', 'radio', 'checkbox', 'scale']),
            'is_required' => fake()->boolean(60),
            'sort_order' => 0,
        ];
    }
}
