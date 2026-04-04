<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    public function mailRecipient(): HasOne
    {
        return $this->hasOne(MailRecipient::class);
    }

    public function mailDeliveryRequests(): HasMany
    {
        return $this->hasMany(MailDeliveryRequest::class);
    }

    public function hasSharedContactDetails(): bool
    {
        $contactInformation = $this->contactInformationSubmission;

        return (bool) (
            filled($this->student_name)
            || filled($this->student_email)
            || $contactInformation?->name
            || $contactInformation?->email
            || $contactInformation?->phone
        );
    }

    public function sharedContactFieldLabels(): array
    {
        $contactInformation = $this->contactInformationSubmission;

        return array_values(array_filter([
            $this->student_name ? 'Naam opgeslagen' : null,
            $this->student_email ? 'E-mailadres opgeslagen' : null,
            $contactInformation?->name && ! $this->student_name ? 'Naam opgeslagen' : null,
            $contactInformation?->email && ! $this->student_email ? 'E-mailadres opgeslagen' : null,
            $contactInformation?->phone ? 'Telefoonnummer opgeslagen' : null,
        ]));
    }

    public function latestMailDeliveryRequest(): ?MailDeliveryRequest
    {
        if ($this->relationLoaded('mailDeliveryRequests')) {
            return $this->mailDeliveryRequests->sortByDesc('id')->first();
        }

        return $this->mailDeliveryRequests()->latest('id')->first();
    }

    public function maskedStudentEmail(): ?string
    {
        if (! filled($this->student_email) || ! str_contains($this->student_email, '@')) {
            return null;
        }

        [$localPart, $domain] = explode('@', $this->student_email, 2);
        $maskedLocal = Str::mask($localPart, '*', 1, max(strlen($localPart) - 2, 1));

        return "{$maskedLocal}@{$domain}";
    }
}
