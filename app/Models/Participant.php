<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'email',
        'name',
        'blocked_at',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'blocked_at' => 'datetime',
    ];

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function pointsHistories(): HasMany
    {
        return $this->hasMany(ParticipantPointsHistory::class);
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
