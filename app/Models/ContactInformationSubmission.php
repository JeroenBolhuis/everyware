<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ContactInformationSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'survey_response_id',
        'name',
        'email',
        'phone',
        'note',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function getNameAttribute(?string $value): ?string
    {
        return $this->decryptContactValue($value);
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $this->encryptContactValue($value);
    }

    public function getEmailAttribute(?string $value): ?string
    {
        return $this->decryptContactValue($value);
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $this->encryptContactValue($value);
    }

    public function getPhoneAttribute(?string $value): ?string
    {
        return $this->decryptContactValue($value);
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $this->encryptContactValue($value);
    }

    private function encryptContactValue(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return Crypt::encryptString($value);
    }

    private function decryptContactValue(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
