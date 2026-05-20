<?php

namespace App\Models;

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
        'withdrawal_token',
        'submitted_at',
        'withdrawn_at',
        'delete_on_date',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'delete_on_date' => 'date',
    ];

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
        $contactInformation = $this->contactInformationSubmission;

        return (bool) ($contactInformation?->name || $contactInformation?->email || $contactInformation?->phone);
    }

    public function sharedContactFieldLabels(): array
    {
        $contactInformation = $this->contactInformationSubmission;

        return array_values(array_filter([
            $contactInformation?->name ? 'Naam opgeslagen' : null,
            $contactInformation?->email ? 'E-mailadres opgeslagen' : null,
            $contactInformation?->phone ? 'Telefoonnummer opgeslagen' : null,
        ]));
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
