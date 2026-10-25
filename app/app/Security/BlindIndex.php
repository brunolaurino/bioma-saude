<?php

namespace App\Security;

use RuntimeException;

/** Índice cego: HMAC-SHA256 com chave própria. Permite achar o e-mail sem guardá-lo em claro. */
class BlindIndex
{
    public static function email(string $email): string
    {
        return self::hmac('email', mb_strtolower(trim($email)));
    }

    /** Códigos de convite e tokens de uso único também viram HMAC antes de ir ao banco. */
    public static function token(string $token): string
    {
        return self::hmac('token', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $token)));
    }

    private static function hmac(string $purpose, string $value): string
    {
        $key = base64_decode((string) config('bioma.index_key'), true);

        if ($key === false || strlen($key) < 32) {
            throw new RuntimeException('BIOMA_INDEX_KEY ausente ou curta.');
        }

        return hash_hmac('sha256', "$purpose|$value", $key);
    }
}
