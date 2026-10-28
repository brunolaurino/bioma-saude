<?php

namespace App\Security;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/** TOTP (RFC 6238) com 10 códigos de recuperação de uso único. */
class TwoFactor
{
    public static function newSecret(): string
    {
        return (new Google2FA)->generateSecretKey(32);
    }

    public static function qrSvg(string $email, string $secret, string $issuer): string
    {
        $uri = (new Google2FA)->getQRCodeUrl($issuer, $email, $secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 0), new SvgImageBackEnd)))->writeString($uri);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }

    public static function verify(string $secret, string $code, ?User $user = null): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }

        $g = new Google2FA;
        $g->setWindow(1);

        return (bool) $g->verifyKey($secret, $code);
    }

    /** @return array{0: list<string>, 1: list<string>} [códigos em claro para mostrar uma vez, hashes para guardar] */
    public static function recoveryCodes(): array
    {
        $plain = [];
        for ($i = 0; $i < 10; $i++) {
            $plain[] = strtoupper(Str::random(5).'-'.Str::random(5));
        }

        return [$plain, array_map(fn ($c) => Hash::make($c), $plain)];
    }

    /** Usa um código de recuperação (some da lista). */
    public static function useRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim($code));
        $codes = (array) $user->two_factor_recovery_codes;

        foreach ($codes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$i]);
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }
}
