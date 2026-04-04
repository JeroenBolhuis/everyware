<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailDeliveryRequest extends Model
{
    protected $fillable = [
        'mail_recipient_id',
        'pseudonym_uuid',
        'survey_id',
        'survey_response_id',
        'mail_template',
        'mail_status',
        'provider',
        'provider_message_id',
        'mail_requested_at',
        'mail_sent_at',
        'mail_failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'mail_requested_at' => 'datetime',
            'mail_sent_at' => 'datetime',
            'mail_failed_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(MailRecipient::class, 'mail_recipient_id');
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }
}
