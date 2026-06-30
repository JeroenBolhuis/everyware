<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ParticipantPointsHistory extends Model
{
    protected $table = 'participant_points_history';

    protected $fillable = [
        'participant_id',
        'amount',
        'source_type',
        'source_id',
        'reason',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
