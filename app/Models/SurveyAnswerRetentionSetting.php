<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswerRetentionSetting extends Model
{
    protected $fillable = [
        'auto_delete_after_days',
    ];

    protected function casts(): array
    {
        return [
            'auto_delete_after_days' => 'integer',
        ];
    }
}
