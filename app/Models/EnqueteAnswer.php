<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnqueteAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\EnqueteAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EnqueteSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EnqueteQuestion::class, 'question_id');
    }
}
