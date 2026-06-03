<?php

namespace App\Models;

use App\Observers\SurveyObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(SurveyObserver::class)]
class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'ends_at',
        'share_token',
        'reward_points',
        'target_academy',
        'created_by_user_id',
    ];

    protected $attributes = [
        'reward_points' => 10,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ends_at' => 'date',
        'reward_points' => 'integer',
    ];

    public function hasEnded(?CarbonInterface $date = null): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        $date ??= today();

        return $this->ends_at->isBefore($date->copy()->startOfDay());
    }

    public function isAcceptingResponses(?CarbonInterface $date = null): bool
    {
        return $this->is_active && ! $this->hasEnded($date);
    }

    public function isVisibleToParticipant(?Participant $participant): bool
    {
        return $this->target_academy === null || $participant?->academy() === $this->target_academy;
    }

    public function scopeVisibleToParticipant(Builder $query, ?Participant $participant): Builder
    {
        return $query->where(function (Builder $query) use ($participant): void {
            $query->whereNull('target_academy');

            if ($participant?->academy() !== null) {
                $query->orWhere('target_academy', $participant->academy());
            }
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
