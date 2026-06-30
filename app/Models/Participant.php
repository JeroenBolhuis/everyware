<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participant extends Model implements AuthenticatableContract
{
    /** @use HasFactory<ParticipantFactory> */
    use Authenticatable;

    use HasFactory;

    /**
     * Temporary holding place used by ParticipantFactory to pass the desired email
     * address from `afterMaking` into `afterCreating` without persisting it to the DB.
     * Not stored in $attributes; not written to the database.
     */
    public ?string $pendingEmail = null;

    protected $fillable = [
        'academy',
        'blocked_at',
        'onboarded_at',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'blocked_at' => 'datetime',
        'onboarded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Participant $participant): void {
            if (filled($participant->public_code)) {
                return;
            }

            $participant->public_code = self::generatePublicCode();
        });
    }

    /**
     * Participants never use a password; access is only via signed magic links.
     */
    public function getAuthPassword(): ?string
    {
        return null;
    }

    /**
     * The identity record lives on the `personal` database connection and holds the email address.
     * Use App\Services\ParticipantService for email lookups instead of this relation directly,
     * as cross-connection eager loading is not supported.
     */
    public function identity(): HasOne
    {
        return $this->hasOne(ParticipantIdentity::class);
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
        return $this->public_code ?? __('Onbekend');
    }

    /**
     * Returns a display name. Email is intentionally not shown here;
     * use ParticipantService::emailForParticipant() when admin access is verified.
     */
    public function displayNameFor(?User $user): string
    {
        return $this->pseudonym();
    }

    /**
     * Participant emails are intentionally never displayed in internal UI.
     */
    public function displayEmailFor(?User $user): string
    {
        return __('Afgeschermd');
    }

    /**
     * Non-PII academy selected by the participant during onboarding.
     */
    public function academy(): ?string
    {
        return $this->academy;
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

    private static function generatePublicCode(): string
    {
        do {
            $code = (string) random_int(10_000_000, 99_999_999);
        } while (self::query()->where('public_code', $code)->exists());

        return $code;
    }
}
