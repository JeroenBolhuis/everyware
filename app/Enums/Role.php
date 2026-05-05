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
    public function description(): string
    {
        return match ($this) {
            self::Admin => __('Kan gebruikers bekijken, aanmaken, bewerken en verwijderen. Kan ook rollen toewijzen. Kan ook alles wat een LIC-medewerker kan.'),
            self::LicEmployee => __('LIC-medewerker rol. Kan gebruikers enquete aanmaken, bekijken, bewerken en verwijderen. Ze kunnen niet alle informatie zien van gebruikers en kunnen ook geen rollen toewijzen.'),
            self::User => __('Standaard gebruiker. Heeft geen toegang tot gebruikersbeheer, maar kan enquete invullen en informatie terug trekken.'),
        };
    }
}
