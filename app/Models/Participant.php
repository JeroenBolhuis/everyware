<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'email',
        'name',
    ];

    protected $casts = [
        'current_points' => 'integer',
    ];

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function pointsHistories(): HasMany
    {
        return $this->hasMany(ParticipantPointsHistory::class);
    }
}
