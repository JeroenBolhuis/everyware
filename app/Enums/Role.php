<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case LicEmployee = 'LICEmployee';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Beheerder'),
            self::LicEmployee => __('LIC-medewerker'),
            self::User => __('Gebruiker'),
        };
    }
}
