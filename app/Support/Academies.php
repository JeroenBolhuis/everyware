<?php

namespace App\Support;

use Illuminate\Support\Str;

class Academies
{
    public static function options(): array
    {
        return [
            'abe' => 'ABE: Academie voor Business en Entrepreneurship',
            'aafm' => 'AAFM: Academie voor Algemeen en Financieel Management',
            'amba' => 'AMBA: Academie voor Marketing en Business Analytics',
            'ahrm' => 'AHRM: Academie voor Human Resource Management',
            'jha' => 'JHA: Juridische Hogeschool Avans-Fontys',
            'atd' => 'ATD: Academie voor Technologie en Design',
            'abi' => 'ABI: Academie voor Bouw en Infra',
            'aci' => 'ACI: Avans Creative Innovation',
            'agz' => 'AGZ: Academie voor Gezondheid en Welzijn',
            'asb-asdb' => 'ASB / ASDB: Academie voor Sociale Studies',
            'atgm' => 'ATGM: Academie voor de Technologie van Gezondheid en Milieu',
            'alst' => 'ALST: Academie voor Life Sciences en Technologie',
            'aaad' => 'AAAd: Avans Academie Associate degrees',
            'avd' => 'AVD: Academie voor Deeltijd',
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $academy): string
    {
        if ($academy === null) {
            return 'Alle studenten';
        }

        return self::options()[$academy] ?? Str::headline($academy);
    }
}
