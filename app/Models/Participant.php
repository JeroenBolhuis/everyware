<?php

namespace App\Models;

use App\Services\ParticipantService;
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
        return __('#:id', ['id' => $this->id]);
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
     * Returns a display email for admin users, fetched via the personal DB service.
     * For non-admins always returns a masked placeholder.
     */
    public function displayEmailFor(?User $user): string
    {
        if ($user?->isAdmin()) {
            /** @var ParticipantService $service */
            $service = app(ParticipantService::class);

            return $service->emailForParticipant($this->id) ?? __('Onbekend');
        }

        return __('Afgeschermd');
    }

    /**
     * Non-PII academy derived from email domain at registration time.
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
}
