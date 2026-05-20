<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::Admin->value,
            Role::LicEmployee->value,
        ]);
    }

    public function view(User $user, Participant $participant): bool
    {
        return $user->hasAnyRole([
            Role::Admin->value,
            Role::LicEmployee->value,
        ]);
    }

    public function correctPoints(User $user, Participant $participant): bool
    {
        return $user->hasAnyRole([
            Role::Admin->value,
            Role::LicEmployee->value,
        ]);
    }
}
