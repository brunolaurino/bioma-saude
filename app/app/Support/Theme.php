<?php

namespace App\Support;

/** Tema escolhido pela pessoa (claro, escuro ou automático, que segue o aparelho). Fica num cookie. */
class Theme
{
    public const COOKIE = 'tema';

    public static function choice(): string
    {
        $v = request()->cookie(self::COOKIE);

        return in_array($v, ['claro', 'escuro'], true) ? $v : 'auto';
    }

    /** Valor do atributo data-theme do <html>, ou null para seguir o aparelho. */
    public static function attr(): ?string
    {
        return ['claro' => 'light', 'escuro' => 'dark'][self::choice()] ?? null;
    }
}
