<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case LICEmployee = 'LICEmployee';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Beheerder'),
            self::LICEmployee => __('LIC-medewerker'),
            self::User => __('Gebruiker'),
        };
    }
}
