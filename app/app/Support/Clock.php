<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** "Hoje" do consultório. O banco guarda UTC; o dia do paciente segue o fuso de São Paulo. */
class Clock
{
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('bioma.timezone'));
    }

    public static function today(): CarbonImmutable
    {
        return self::now()->startOfDay();
    }

    public static function todayIso(): string
    {
        return self::today()->toDateString();
    }

    /** Converte um instante salvo em UTC para o fuso local. */
    public static function local($value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value)->setTimezone(config('bioma.timezone')) : null;
    }

    /** Lê "dd/mm/aaaa" ou "aaaa-mm-dd". Devolve null se a data não existir. */
    public static function parseDate(?string $value): ?CarbonImmutable
    {
        $value = trim((string) $value);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($mo, $d, $y) ? CarbonImmutable::create($y, $mo, $d, 0, 0, 0, config('bioma.timezone')) : null;
    }
}
