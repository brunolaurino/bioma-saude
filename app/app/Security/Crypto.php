<?php

namespace App\Security;

use RuntimeException;

/**
 * Cifra campo a campo com XChaCha20-Poly1305 e a DEK do titular. O dado associado leva tabela,
 * coluna e id da linha: um valor copiado para outra linha ou coluna não decifra.
 */
class Crypto
{
    private const PREFIX = 'bc1:';

    public static function encrypt(string $subject, string $context, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $plain = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $ct = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, $context, $nonce, KeyVault::dek($subject, true));
        sodium_memzero($plain);

        return self::PREFIX.base64_encode($nonce.$ct);
    }

    public static function decrypt(string $subject, string $context, ?string $blob): mixed
    {
        if ($blob === null || $blob === '') {
            return null;
        }

        if (! str_starts_with($blob, self::PREFIX)) {
            throw new RuntimeException("Valor sem cifra em $context.");
        }

        $raw = base64_decode(substr($blob, strlen(self::PREFIX)), true);
        $n = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $plain = $raw === false ? false : sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw, $n), $context, substr($raw, 0, $n), KeyVault::dek($subject));

        if ($plain === false) {
            throw new RuntimeException("Valor adulterado ou fora do lugar em $context.");
        }

        return json_decode($plain, true, flags: JSON_THROW_ON_ERROR);
    }

    public static function isCiphertext(?string $value): bool
    {
        return $value !== null && str_starts_with($value, self::PREFIX);
    }

    /** Cifra um arquivo inteiro em blocos (secretstream). O id do arquivo entra no primeiro bloco. */
    public static function encryptFile(string $subject, string $fileId, string $sourcePath, string $targetPath): void
    {
        $in = fopen($sourcePath, 'rb');
        $out = fopen($targetPath, 'wb');
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push(KeyVault::dek($subject, true));
        fwrite($out, $header);

        $first = true;
        do {
            $chunk = fread($in, 64 * 1024);
            $last = feof($in);
            $tag = $last ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
            fwrite($out, sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, $first ? "file|$fileId" : '', $tag));
            $first = false;
        } while (! $last);

        fclose($in);
        fclose($out);
    }

    /** Decifra para um caminho temporário. Quem chama apaga o arquivo depois de usar. */
    public static function decryptFile(string $subject, string $fileId, string $sourcePath, string $targetPath): void
    {
        $in = fopen($sourcePath, 'rb');
        $out = fopen($targetPath, 'wb');
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull(fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES), KeyVault::dek($subject));

        $first = true;
        $done = false;
        while (! feof($in)) {
            $chunk = fread($in, 64 * 1024 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);
            if ($chunk === '' || $chunk === false) {
                break;
            }
            $res = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $chunk, $first ? "file|$fileId" : '');
            if ($res === false) {
                fclose($in);
                fclose($out);
                @unlink($targetPath);
                throw new RuntimeException("Arquivo $fileId adulterado.");
            }
            [$plain, $tag] = $res;
            fwrite($out, $plain);
            $first = false;
            if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                $done = true;
                break;
            }
        }

        fclose($in);
        fclose($out);

        if (! $done) {
            @unlink($targetPath);
            throw new RuntimeException("Arquivo $fileId incompleto.");
        }
    }
}
