<?php

namespace App\Security;

/**
 * Regras de senha do escopo: mínimo de 10 caracteres e checagem contra uma lista local de
 * senhas vazadas (resources/security/senhas-vazadas.txt, sem consulta externa).
 */
class Passwords
{
    private static ?array $leaked = null;

    /** Devolve a mensagem de erro, ou null se a senha serve. */
    public static function problem(string $password, array $personal = []): ?string
    {
        $min = (int) config('bioma.password_min');

        if (mb_strlen($password) < $min) {
            return "Use pelo menos $min caracteres.";
        }

        if (mb_strlen($password) > 128) {
            return 'Use no máximo 128 caracteres.';
        }

        if (self::isLeaked($password)) {
            return 'Essa senha aparece em vazamentos conhecidos. Escolha outra.';
        }

        foreach ($personal as $p) {
            $p = mb_strtolower(trim((string) $p));
            if (mb_strlen($p) >= 4 && str_contains(mb_strtolower($password), $p)) {
                return 'Não use seu nome ou e-mail na senha.';
            }
        }

        if (count(array_unique(mb_str_split($password))) < 4) {
            return 'Use uma senha com mais variedade de caracteres.';
        }

        return null;
    }

    public static function isLeaked(string $password): bool
    {
        if (self::$leaked === null) {
            $file = resource_path('security/senhas-vazadas.txt');
            $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            self::$leaked = array_fill_keys(array_map('mb_strtolower', $lines), true);
        }

        return isset(self::$leaked[mb_strtolower($password)]);
    }
}
