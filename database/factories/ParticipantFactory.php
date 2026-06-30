<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\ParticipantIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // academy is selected during onboarding and stored as non-PII.
            // The actual email lives in participant_identities (personal DB), created in configure().
            'academy' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Participant $participant) {
            // Capture any email passed via state (e.g. withEmail) before removing
            // it from the Eloquent attributes so it is never written to the DB.
            $participant->pendingEmail = isset($participant->getAttributes()['email'])
                ? $participant->getAttributes()['email']
                : null;

            $participant->offsetUnset('email');
        })->afterCreating(function (Participant $participant) {
            $email = $participant->pendingEmail ?? fake()->unique()->safeEmail();

            ParticipantIdentity::updateOrCreate(
                ['participant_id' => $participant->id],
                ['email' => $email],
            );
        });
    }

    /**
     * Convenience state to set a specific email address.
     * The email is stored in participant_identities (personal DB).
     */
    public function withEmail(string $email): static
    {
        return $this->state(['email' => $email]);
    }
}
