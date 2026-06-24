<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum Academy: string
{
    case ABE = 'abe';
    case AAFM = 'aafm';
    case AMBA = 'amba';
    case AHRM = 'ahrm';
    case JHA = 'jha';
    case ATD = 'atd';
    case ABI = 'abi';
    case ACI = 'aci';
    case AGZ = 'agz';
    case ASB_ASDB = 'asb-asdb';
    case ATGM = 'atgm';
    case ALST = 'alst';
    case AAAD = 'aaad';
    case AVD = 'avd';

    public function label(): string
    {
        return match ($this) {
            self::ABE => 'ABE: Academie voor Business en Entrepreneurship',
            self::AAFM => 'AAFM: Academie voor Algemeen en Financieel Management',
            self::AMBA => 'AMBA: Academie voor Marketing en Business Analytics',
            self::AHRM => 'AHRM: Academie voor Human Resource Management',
            self::JHA => 'JHA: Juridische Hogeschool Avans-Fontys',
            self::ATD => 'ATD: Academie voor Technologie en Design',
            self::ABI => 'ABI: Academie voor Bouw en Infra',
            self::ACI => 'ACI: Avans Creative Innovation',
            self::AGZ => 'AGZ: Academie voor Gezondheid en Welzijn',
            self::ASB_ASDB => 'ASB / ASDB: Academie voor Sociale Studies',
            self::ATGM => 'ATGM: Academie voor de Technologie van Gezondheid en Milieu',
            self::ALST => 'ALST: Academie voor Life Sciences en Technologie',
            self::AAAD => 'AAAd: Avans Academie Associate degrees',
            self::AVD => 'AVD: Academie voor Deeltijd',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $academy) {
            $options[$academy->value] = $academy->label();
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_map(
            fn (self $academy): string => $academy->value,
            self::cases(),
        );
    }

    public static function labelFor(?string $academy): string
    {
        if ($academy === null) {
            return 'Alle studenten';
        }

        return self::tryFrom($academy)?->label() ?? Str::headline($academy);
    }
}
