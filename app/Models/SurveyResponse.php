<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'user_id',
        'survey_id',
        'survey_question_id',
        'answer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
