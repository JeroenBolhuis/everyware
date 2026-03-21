<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'student_name',
        'student_email',
        'withdrawal_token',
        'submitted_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function contactInformationSubmission(): HasOne
    {
        return $this->hasOne(ContactInformationSubmission::class);
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
}
