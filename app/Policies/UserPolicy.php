<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasRole(Role::Admin->value)) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        if ($model->hasRole(Role::Admin->value) && User::role(Role::Admin->value)->count() <= 1) {
            return false;
        }

        return true;
    }
}
