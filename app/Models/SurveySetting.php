<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveySetting extends Model
{
    protected $fillable = [
        'id',
        'retention_years',
        'upcoming_warning_days',
    ];

    protected function casts(): array
    {
        return [
            'retention_years' => 'integer',
            'upcoming_warning_days' => 'integer',
        ];
    }
}
