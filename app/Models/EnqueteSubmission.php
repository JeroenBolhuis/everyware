<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnqueteSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\EnqueteSubmissionFactory> */
    use HasFactory;

    protected $table = 'enquete_submissions';

    protected $fillable = [
        'enquete_id',
        'submitted_at',
        'respondent_key',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function enquete(): BelongsTo
    {
        return $this->belongsTo(Enquete::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EnqueteAnswer::class, 'submission_id');
    }
}
