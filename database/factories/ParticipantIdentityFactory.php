<?php

namespace Database\Factories;

use App\Models\ParticipantIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantIdentity>
 */
class ParticipantIdentityFactory extends Factory
{
    protected $model = ParticipantIdentity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
