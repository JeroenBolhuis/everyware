<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => RoleEnum::class,
            'password' => 'hashed',
        ];
    }

    public function hasRole(RoleEnum|string $role): bool
    {
        $role = $role instanceof RoleEnum ? $role : RoleEnum::tryFrom($role);

        return $role !== null && $this->role === $role;
    }

    /**
     * @param  array<int, RoleEnum|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeRole(Builder $query, RoleEnum|string $role): Builder
    {
        $role = $role instanceof RoleEnum ? $role : RoleEnum::tryFrom($role);

        return $query->where('role', $role?->value);
    }

    public function assignRole(RoleEnum|string $role): static
    {
        $role = $role instanceof RoleEnum ? $role : RoleEnum::from($role);

        $this->forceFill(['role' => $role])->save();

        return $this;
    }

    public function removeRole(RoleEnum|string $role): static
    {
        if ($this->hasRole($role)) {
            $this->forceFill(['role' => RoleEnum::User])->save();
        }

        return $this;
    }

    /**
     * @param  array<int, RoleEnum|string>  $roles
     */
    public function syncRoles(array $roles): static
    {
        $this->assignRole($this->highestRole($roles));

        return $this;
    }

    public function getRoleNames(): Collection
    {
        return collect([$this->role->value]);
    }

    /**
     * @param  array<int, RoleEnum|string>  $roles
     */
    private function highestRole(array $roles): RoleEnum
    {
        $roleEnums = collect($roles)
            ->map(fn (RoleEnum|string $role): ?RoleEnum => $role instanceof RoleEnum ? $role : RoleEnum::tryFrom($role))
            ->filter();

        foreach ([RoleEnum::Admin, RoleEnum::LicEmployee, RoleEnum::User] as $role) {
            if ($roleEnums->contains($role)) {
                return $role;
            }
        }

        return RoleEnum::User;
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::Admin);
    }

    public function isLicEmployee(): bool
    {
        return $this->hasRole(RoleEnum::LicEmployee);
    }

    public function isLicMedewerker(): bool
    {
        return $this->isLicEmployee();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageSurveys(): bool
    {
        return $this->hasAnyRole([
            RoleEnum::Admin->value,
            RoleEnum::LicEmployee->value,
        ]);
    }

    public function canReviewSurveyResponses(): bool
    {
        return $this->hasAnyRole([
            RoleEnum::Admin->value,
            RoleEnum::LicEmployee->value,
        ]);
    }

    public function canAccessAdminArea(): bool
    {
        return $this->canManageUsers() || $this->canReviewSurveyResponses();
    }

    public function homeRouteName(): string
    {
        return $this->canManageSurveys()
            ? 'survey-manager.index'
            : 'surveys.index';
    }
}
