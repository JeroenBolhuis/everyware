<?php

namespace App\Models;

use App\Actions\Surveys\SurveyRetentionSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'participant_id',
        'is_anonymous',
        'withdrawal_token',
        'submitted_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'is_anonymous' => 'boolean',
    ];

    public function deleteOnDate(): ?CarbonInterface
    {
        $referenceDate = $this->submitted_at ?? $this->created_at;

        return $referenceDate->copy()->addYears(app(SurveyRetentionSettings::class)->retentionYears());
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function contactInformationSubmission(): HasOne
    {
        return $this->hasOne(ContactInformationSubmission::class);
    }

    public function participantPointsHistories(): MorphMany
    {
        return $this->morphMany(ParticipantPointsHistory::class, 'source');
    }

    public function scopeVisibleInResults(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereDoesntHave('participant')
                ->orWhereHas('participant', fn (Builder $participantQuery) => $participantQuery->whereNull('blocked_at'));
        });
    }

    public function hasSharedContactDetails(): bool
    {
        return ! $this->is_anonymous;
    }

    public function sharedContactFieldLabels(): array
    {
        return $this->is_anonymous ? [] : ['E-mailadres zichtbaar voor LIC'];
    }

    public function awardedPoints(): int
    {
        return (int) $this->participantPointsHistories->sum('amount');
    }

    public function totalPoints(): int
    {
        return (int) ($this->participant?->current_points ?? 0);
    }
}
