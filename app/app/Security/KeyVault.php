<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cofre das chaves de dados (DEK). Cada paciente tem uma DEK de 256 bits, guardada cifrada pela
 * chave mestra (KEK) numa conexão de banco separada. Destruir a DEK torna ilegíveis os dados
 * do paciente, inclusive em backups antigos do banco principal.
 *
 * Este adaptador usa uma KEK local vinda de segredo do Docker. Em produção a mesma interface
 * chama o KMS do provedor para cifrar e decifrar as DEKs, e a KEK nunca sai do KMS.
 */
class KeyVault
{
    public const ENV = 'env';

    /** @var array<string, string> DEKs já abertas nesta requisição. */
    private static array $open = [];

    public static function dek(string $subject, bool $create = false): string
    {
        if (isset(self::$open[$subject])) {
            return self::$open[$subject];
        }

        $row = self::table()->where('subject', $subject)->first();

        if ($row && $row->destroyed_at) {
            throw new KeyDestroyed("A chave de $subject foi destruída.");
        }

        if (! $row) {
            if (! $create && $subject !== self::ENV) {
                throw new RuntimeException("Não há chave para $subject.");
            }
            $dek = sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
            $version = self::currentVersion();
            self::table()->insert([
                'subject' => $subject,
                'wrapped_dek' => self::wrap($dek, $subject, $version),
                'kek_version' => $version,
                'created_at' => now(),
            ]);

            return self::$open[$subject] = $dek;
        }

        return self::$open[$subject] = self::unwrap($row->wrapped_dek, $subject, (int) $row->kek_version);
    }

    /** Eliminação definitiva: a DEK some e o cofre guarda só o registro de que existiu. */
    public static function destroy(string $subject): void
    {
        self::table()->where('subject', $subject)->update(['wrapped_dek' => '', 'destroyed_at' => now()]);
        unset(self::$open[$subject]);
    }

    /** Recifra todas as DEKs com a KEK atual. Os dados não mudam. Devolve quantas foram recifradas. */
    public static function rotate(): int
    {
        $version = self::currentVersion();
        $count = 0;

        foreach (self::table()->whereNull('destroyed_at')->where('kek_version', '!=', $version)->get() as $row) {
            $dek = self::unwrap($row->wrapped_dek, $row->subject, (int) $row->kek_version);
            self::table()->where('subject', $row->subject)->update([
                'wrapped_dek' => self::wrap($dek, $row->subject, $version),
                'kek_version' => $version,
                'rotated_at' => now(),
            ]);
            sodium_memzero($dek);
            $count++;
        }

        return $count;
    }

    /** Fecha as chaves abertas (fim de requisição, testes). */
    public static function forget(): void
    {
        foreach (self::$open as $k => $v) {
            sodium_memzero(self::$open[$k]);
        }
        self::$open = [];
    }

    private static function wrap(string $dek, string $subject, int $version): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ct = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($dek, "dek|$subject|$version", $nonce, self::kek($version));

        return base64_encode($nonce.$ct);
    }

    private static function unwrap(string $wrapped, string $subject, int $version): string
    {
        $raw = base64_decode($wrapped, true);
        $n = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $dek = $raw === false ? false : sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw, $n), "dek|$subject|$version", substr($raw, 0, $n), self::kek($version));

        if ($dek === false) {
            throw new RuntimeException("Não foi possível abrir a chave de $subject.");
        }

        return $dek;
    }

    private static function kek(int $version): string
    {
        $keys = config('bioma.kek');
        $key = isset($keys[$version]) ? base64_decode($keys[$version], true) : false;

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new RuntimeException("KEK versão $version ausente ou inválida. Confira BIOMA_KEK.");
        }

        return $key;
    }

    private static function currentVersion(): int
    {
        return (int) config('bioma.kek_current');
    }

    private static function table()
    {
        return DB::connection(config('bioma.keyring_connection'))->table('patient_keys');
    }
}
