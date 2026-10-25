<?php

namespace App\Domain;

use App\Models\Invitation;
use App\Models\Patient;
use App\Models\User;
use App\Security\Audit;
use App\Security\BlindIndex;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Convites de uso único. O código vale 7 dias, só o HMAC vai para o banco e o convite
 * anterior da mesma pessoa deixa de valer quando um novo é gerado.
 */
class Invites
{
    // Sem 0/O, 1/I/L: o paciente digita o código lido no WhatsApp.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** @return string código em claro, formatado XXXX-XXXX, para mostrar uma única vez. */
    public static function forPatient(Patient $patient, User $user): string
    {
        return self::create('patient', $user, $patient);
    }

    public static function forProfessional(User $user): string
    {
        return self::create('professional', $user, null);
    }

    public static function find(string $code, string $kind): ?Invitation
    {
        return Invitation::where('code_hash', BlindIndex::token($code))->where('kind', $kind)
            ->whereNull('used_at')->where('expires_at', '>', now())->first();
    }

    public static function format(string $code): string
    {
        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    /** E-mail com o código. O texto não traz nenhum dado de saúde. */
    public static function mail(User $user, string $code, string $who): void
    {
        $body = $user->isProfessional()
            ? "Olá! $who convidou você para a equipe no Bioma CD.\n\nAbra este link para criar sua conta:\n\n".route('setup', str_replace('-', '', $code))."\n\nO link vale por 7 dias e só pode ser usado uma vez."
            : "Olá! $who convidou você para o Bioma CD.\n\nAbra ".route('first')." e digite o código:\n\n$code\n\nO código vale por 7 dias e só pode ser usado uma vez.";
        try {
            Mail::raw($body, function ($m) use ($user) {
                $m->to($user->email)->subject('Seu convite para o Bioma CD');
            });
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar e-mail de convite.');
        }
    }

    private static function create(string $kind, User $user, ?Patient $patient): string
    {
        Invitation::where('user_id', $user->id)->whereNull('used_at')->update(['expires_at' => now()]);

        $len = (int) config('bioma.invite.length', 8);
        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        Invitation::create([
            'kind' => $kind,
            'patient_id' => $patient?->id,
            'user_id' => $user->id,
            'code_hash' => BlindIndex::token($code),
            'expires_at' => now()->addDays((int) config('bioma.invite.days', 7)),
            'created_by' => auth()->id(),
        ]);
        Audit::log('invite.create', 'user:'.$user->id, $patient?->id, ['kind' => $kind]);

        return self::format($code);
    }
}
