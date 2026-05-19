<?php

namespace App\Models;

use App\Observers\SurveyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(SurveyObserver::class)]
class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'share_token',
        'reward_points',
        'created_by',
    ];

    protected $attributes = [
        'reward_points' => 10,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reward_points' => 'integer',
    ];

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
        return $this->belongsTo(User::class, 'created_by');
    }
}
