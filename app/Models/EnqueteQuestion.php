<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnqueteQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\EnqueteQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'enquete_id',
        'label',
        'type',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function enquete(): BelongsTo
    {
        return $this->belongsTo(Enquete::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EnqueteAnswer::class, 'question_id');
    }
}
