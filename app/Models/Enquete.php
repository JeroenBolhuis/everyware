<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquete extends Model
{
    /** @use HasFactory<\Database\Factories\EnqueteFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EnqueteQuestion::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(EnqueteSubmission::class);
    }
}
