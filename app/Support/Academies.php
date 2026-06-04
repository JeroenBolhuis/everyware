<?php

namespace App\Support;

use Illuminate\Support\Str;

class Academies
{
    public static function options(): array
    {
        return [
            'avans' => 'Avans',
            'fontys' => 'Fontys',
            'hogeschool-utrecht' => 'Hogeschool Utrecht',
            'hogeschool-rotterdam' => 'Hogeschool Rotterdam',
            'inholland' => 'Inholland',
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

    public static function fromEmail(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return null;
        }

        $domain = Str::of($email)->afterLast('@')->lower()->toString();

        foreach (self::emailDomains() as $academy => $domains) {
            foreach ($domains as $allowedDomain) {
                if ($domain === $allowedDomain || Str::endsWith($domain, '.'.$allowedDomain)) {
                    return $academy;
                }
            }
        }

        return null;
    }

    private static function emailDomains(): array
    {
        return [
            'avans' => ['avans.nl'],
            'fontys' => ['fontys.nl'],
            'hogeschool-utrecht' => ['hu.nl', 'student.hu.nl'],
            'hogeschool-rotterdam' => ['hr.nl', 'student.hr.nl'],
            'inholland' => ['inholland.nl', 'student.inholland.nl'],
        ];
    }
}
