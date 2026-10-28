<?php

namespace App\Security;

use App\Models\CareTeam;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * As 3 perguntas de toda leitura clínica: quem pede pode ver esse paciente? o módulo foi
 * liberado para esse profissional? e, para gravar, o módulo está ligado para esse paciente?
 * O master vê todos os pacientes do ambiente e todos os módulos.
 */
class Access
{
    /** Módulos que não se desligam (a LGPD exige esses canais) ou que são do prontuário. */
    public const CORE = ['resumo', 'notas', 'privacidade'];

    /** @var array<string, array<string, ?array>> cache por requisição: user => patient => módulos */
    private static array $team = [];

    public static function patients(User $user): Builder
    {
        $q = Patient::query();

        if ($user->isMaster()) {
            return $q;
        }

        return $q->whereIn('id', CareTeam::where('professional_id', $user->id)->select('patient_id'));
    }

    public static function canView(User $user, Patient $patient, ?string $module = null): bool
    {
        if ($user->isPatient()) {
            return $patient->user_id === $user->id && ($module === null || in_array($module, self::CORE, true) || Modules::enabled($module, $patient->id));
        }

        if (! $user->isProfessional() || $user->status !== 'active') {
            return false;
        }

        if ($user->isMaster()) {
            return true;
        }

        $allowed = self::teamModules($user, $patient);
        if ($allowed === null) {
            return false;
        }

        return $module === null || in_array($module, self::CORE, true) || in_array($module, $allowed, true);
    }

    /** Gravar exige, além de ver, o módulo ligado para o paciente. Histórico de módulo desligado é só leitura. */
    public static function canWrite(User $user, Patient $patient, string $module): bool
    {
        return self::canView($user, $patient, $module)
            && (in_array($module, self::CORE, true) || Modules::enabled($module, $patient->id));
    }

    public static function authorizeView(User $user, Patient $patient, ?string $module = null): void
    {
        abort_unless(self::canView($user, $patient, $module), 403, 'Você não tem acesso a estes dados.');
    }

    public static function authorizeWrite(User $user, Patient $patient, string $module): void
    {
        abort_unless(self::canWrite($user, $patient, $module), 403, 'Este módulo está desligado ou não foi liberado para você.');
    }

    /** @return ?array lista de módulos liberados, ou null se o profissional não está na equipe. */
    public static function teamModules(User $user, Patient $patient): ?array
    {
        if (! array_key_exists($patient->id, self::$team[$user->id] ?? [])) {
            $row = CareTeam::where('patient_id', $patient->id)->where('professional_id', $user->id)->first();
            self::$team[$user->id][$patient->id] = $row ? (array) $row->modules : null;
        }

        return self::$team[$user->id][$patient->id];
    }

    public static function flush(): void
    {
        self::$team = [];
    }
}
