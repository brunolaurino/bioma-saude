<?php

namespace App\Security;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

/**
 * Ciência do aviso de privacidade e consentimentos opcionais (fotos, compartilhar desabafos por
 * padrão). Cada aceite e cada revogação é uma linha nova com versão, data, IP e aparelho.
 */
class Consents
{
    public const PURPOSES = [
        'privacy_notice' => 'Ciência do aviso de privacidade',
        'photos' => 'Fotos corporais de evolução',
        'journal_share' => 'Compartilhar desabafos com a equipe por padrão',
    ];

    public static function accept(Patient $patient, string $purpose, ?int $noticeVersion = null): void
    {
        self::write($patient, $purpose, 'accept', $noticeVersion ?? self::currentNoticeVersion());
    }

    public static function revoke(Patient $patient, string $purpose): void
    {
        self::write($patient, $purpose, 'revoke', self::currentNoticeVersion());
    }

    public static function has(Patient|string $patient, string $purpose): bool
    {
        $id = $patient instanceof Patient ? $patient->id : $patient;
        $last = DB::table('consents')->where('patient_id', $id)->where('purpose', $purpose)->orderByDesc('id')->first();

        return $last !== null && $last->action === 'accept';
    }

    /** O paciente precisa dar nova ciência quando sai uma versão do aviso com mudança relevante. */
    public static function needsNoticeAcceptance(Patient $patient): bool
    {
        $required = (int) DB::table('privacy_notices')->where('relevant_change', true)->max('version');
        if ($required === 0) {
            return false;
        }
        $accepted = (int) DB::table('consents')->where('patient_id', $patient->id)->where('purpose', 'privacy_notice')->where('action', 'accept')->max('notice_version');

        return $accepted < $required;
    }

    public static function currentNoticeVersion(): int
    {
        return (int) DB::table('privacy_notices')->max('version');
    }

    public static function history(Patient $patient)
    {
        return DB::table('consents')->where('patient_id', $patient->id)->orderByDesc('id')->get();
    }

    private static function write(Patient $patient, string $purpose, string $action, ?int $version): void
    {
        DB::table('consents')->insert([
            'patient_id' => $patient->id,
            'purpose' => $purpose,
            'action' => $action,
            'notice_version' => $version ?: null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : mb_substr((string) request()->userAgent(), 0, 255),
            'created_at' => now()->format('Y-m-d H:i:s.u'),
        ]);
        Audit::log("consent.$action", "consent:$purpose", $patient->id, ['version' => $version]);
    }
}
