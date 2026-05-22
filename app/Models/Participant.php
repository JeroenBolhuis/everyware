<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model implements AuthenticatableContract
{
    /** @use HasFactory<ParticipantFactory> */
    use Authenticatable;

    use HasFactory;

    protected $fillable = [
        'email',
        'blocked_at',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'blocked_at' => 'datetime',
    ];

    /**
     * Participants never use a password; access is only via signed magic links.
     */
    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function pointsHistories(): HasMany
    {
        return $this->hasMany(ParticipantPointsHistory::class);
    }

    public function pseudonym(): string
    {
        return __('#:id', ['id' => $this->id]);
    }

    public function displayNameFor(?User $user): string
    {
        return $this->pseudonym();
    }

    public function displayEmailFor(?User $user): string
    {
        if ($user?->isAdmin()) {
            return $this->email;
        }

        return __('Afgeschermd');
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function block(): void
    {
        if ($this->isBlocked()) {
            return;
        }

        $this->forceFill([
            'blocked_at' => now(),
        ])->save();
    }
}
