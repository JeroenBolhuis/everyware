<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
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
            'password' => 'hashed',
        ];
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
        return $this->hasRole(RoleEnum::Admin->value);
    }

    public function isLicMedewerker(): bool
    {
        return $this->hasRole(RoleEnum::LicMedewerker->value);
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageSurveys(): bool
    {
        return $this->hasAnyRole([
            RoleEnum::Admin->value,
            RoleEnum::LicMedewerker->value,
        ]);
    }

    public function canReviewSurveyResponses(): bool
    {
        return $this->hasAnyRole([
            RoleEnum::Admin->value,
            RoleEnum::LicMedewerker->value,
        ]);
    }

    public function canAccessAdminArea(): bool
    {
        return $this->canManageUsers() || $this->canReviewSurveyResponses();
    }
}