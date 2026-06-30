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
            self::Admin => __('Volledige beheerrol. Kan gebruikers, rollen, instellingen en persoonsgegevens beheren.'),
            self::LicEmployee => __('Kan enquêtes en inzendingen beheren zonder toegang tot persoonsgegevens zoals e-mailadressen.'),
            self::User => __('Standaard account zonder toegang tot beheer- of enquêtebeheerfuncties.'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            'account_access' => __('Eigen account gebruiken'),
            'manage_surveys' => __('Enquêtes aanmaken, bewerken en sluiten'),
            'review_responses' => __('Inzendingen en antwoorden bekijken'),
            'delete_responses' => __('Volledige inzendingen verwijderen'),
            'export_anonymized_feedback' => __('Exports zonder persoonsgegevens maken'),
            'view_participant_points' => __('Deelnemers als pseudoniem met punten bekijken'),
            'correct_points' => __('Punten afboeken of corrigeren'),
            'view_personal_data' => __('Persoonsgegevens en e-mailadressen bekijken'),
            'block_email_addresses' => __('E-mailadressen blokkeren'),
            'manage_retention' => __('Bewaartermijn aanpassen'),
            'manage_users' => __('Gebruikersaccounts beheren'),
            'assign_roles' => __('Rollen toewijzen'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function grantedPermissions(): array
    {
        return match ($this) {
            self::Admin => array_keys(self::permissions()),
            self::LicEmployee => [
                'account_access',
                'manage_surveys',
                'review_responses',
                'delete_responses',
                'export_anonymized_feedback',
                'view_participant_points',
                'correct_points',
            ],
            self::User => [
                'account_access',
            ],
        };
    }

    public function grantsPermission(string $permission): bool
    {
        return in_array($permission, $this->grantedPermissions(), true);
    }
}
