<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Formatos brasileiros: 78,8 kg, 1.561 kcal, 05/10/2026, "há 3 dias". */
class Fmt
{
    public const WD = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    public const WDL = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];

    public const MON = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    public const MONL = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    public static function num(float|int|null $v, int $dec = 1): string
    {
        return $v === null ? '—' : number_format((float) $v, $dec, ',', '.');
    }

    public static function int(float|int|null $v): string
    {
        return self::num($v, 0);
    }

    /** Lê número digitado no padrão brasileiro: "78,8", "1.561", "1561", "78.8". */
    public static function parseNumber(?string $s): ?float
    {
        $t = trim((string) $s);
        if ($t === '') {
            return null;
        }
        if (str_contains($t, ',')) {
            $t = str_replace(',', '.', str_replace('.', '', $t));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
            $t = str_replace('.', '', $t);
        }

        return is_numeric($t) ? (float) $t : null;
    }

    private static function d($date): CarbonImmutable
    {
        return $date instanceof CarbonInterface ? CarbonImmutable::instance($date) : CarbonImmutable::parse($date);
    }

    public static function dm($date): string
    {
        return self::d($date)->format('d/m');
    }

    public static function dmy($date): string
    {
        return self::d($date)->format('d/m/Y');
    }

    /** "1 set" */
    public static function short($date): string
    {
        $d = self::d($date);

        return $d->day.' '.self::MON[$d->month - 1];
    }

    public static function wd($date): string
    {
        return self::WD[self::d($date)->dayOfWeek];
    }

    public static function wdl($date): string
    {
        return self::WDL[self::d($date)->dayOfWeek];
    }

    public static function monthYear($date): string
    {
        $d = self::d($date);

        return self::MONL[$d->month - 1].' de '.$d->year;
    }

    public static function daysAgo($date): int
    {
        return (int) self::d($date)->startOfDay()->diffInDays(Clock::today(), false);
    }

    public static function rel($date): string
    {
        $n = self::daysAgo($date);

        return match (true) {
            $n === 0 => 'hoje',
            $n === 1 => 'ontem',
            $n < 0 => 'em '.(-$n).' dias',
            default => "há $n dias",
        };
    }

    public static function time($dt): string
    {
        $d = Clock::local($dt);

        return $d->format('H').'h'.$d->format('i');
    }

    public static function size(int $bytes): string
    {
        return $bytes > 1048576 ? self::num($bytes / 1048576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    }

    public static function initials(string $name): string
    {
        $name = preg_replace('/^(Dra?\.|Prof\.|Psic\.)\s*/u', '', trim($name));
        $parts = array_slice(array_values(array_filter(explode(' ', $name))), 0, 2);

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), $parts)));
    }

    public static function plural(int $n, string $one, string $many): string
    {
        return $n === 1 ? $one : $many;
    }
}
