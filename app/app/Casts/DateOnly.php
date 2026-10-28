<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Coluna DATE guardada sempre como "Y-m-d" (o cast padrão do Laravel grava "Y-m-d H:i:s", o que
 * quebra comparações de dia no SQLite dos testes). Lido como CarbonImmutable à meia-noite.
 */
class DateOnly implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse(substr((string) $value, 0, 10))->startOfDay();
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof \DateTimeInterface ? CarbonImmutable::instance($value) : CarbonImmutable::parse((string) $value))->format('Y-m-d');
    }
}
