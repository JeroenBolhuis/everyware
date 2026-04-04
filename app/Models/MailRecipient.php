<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailRecipient extends Model
{
    protected $fillable = [
        'survey_id',
        'survey_response_id',
        'pseudonym_uuid',
        'full_name_encrypted',
        'email_encrypted',
        'email_hash',
        'consent_source',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'full_name_encrypted' => 'encrypted',
            'email_encrypted' => 'encrypted',
            'revoked_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function deliveryRequests(): HasMany
    {
        return $this->hasMany(MailDeliveryRequest::class);
    }
}
