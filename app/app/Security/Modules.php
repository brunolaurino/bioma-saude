<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;

class Modules
{
    /** Módulos ativáveis pelo master, na ordem do protótipo. */
    public const ALL = [
        'metas' => 'Metas diárias',
        'sintomas' => 'Sintomas',
        'bioimpedancia' => 'Bioimpedância',
        'fotos' => 'Fotos de evolução',
        'exames' => 'Exames',
        'nutricao' => 'Nutrição',
        'atividade' => 'Atividade física',
        'saude_mental' => 'Saúde mental',
        'lembretes' => 'Lembretes',
        'conquistas' => 'Conquistas',
        'equipe' => 'Equipe multidisciplinar',
        'agenda' => 'Agenda',
    ];

    public const HINTS = [
        'metas' => 'Água, proteína, frutas e verduras',
        'sintomas' => 'Efeitos colaterais e intensidade',
        'bioimpedancia' => 'Peso, gordura, músculo, visceral e TMB',
        'fotos' => 'Frente, lado e costas. Pede consentimento do paciente',
        'exames' => 'Laudos e exames em PDF ou foto',
        'nutricao' => 'Plano alimentar e histórico de consultas',
        'atividade' => 'Treinos de força e aeróbicos',
        'saude_mental' => 'Desabafos, humor e sessões de terapia',
        'lembretes' => 'Avisos no app sem dado de saúde',
        'conquistas' => 'Marcos de hábito no perfil',
        'equipe' => 'Profissionais que acompanham o paciente',
        'agenda' => 'Consultas marcadas',
    ];

    /** Metas que o master liga e desliga dentro do módulo Metas diárias. */
    public const GOALS = ['water' => 'Água', 'protein' => 'Proteína', 'fvl' => 'Frutas, verduras e legumes'];

    /** @var array<string, array<string, bool>> cache por requisição */
    private static array $cache = [];

    public static function exists(string $module): bool
    {
        return array_key_exists($module, self::ALL);
    }

    /** Estado efetivo: a exceção do paciente vence o padrão do ambiente. */
    public static function enabled(string $module, ?string $patientId = null): bool
    {
        if (! self::exists($module)) {
            return false;
        }

        return self::map($patientId)[$module];
    }

    /** @return array<string, bool> estado efetivo de todos os módulos para o paciente (ou o padrão). */
    public static function map(?string $patientId = null): array
    {
        $cacheKey = $patientId ?? '*';
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $defaults = DB::table('module_settings')->pluck('enabled', 'module')->all();
        $overrides = $patientId
            ? DB::table('patient_module_overrides')->where('patient_id', $patientId)->pluck('enabled', 'module')->all()
            : [];

        $map = [];
        foreach (array_keys(self::ALL) as $m) {
            $map[$m] = (bool) ($overrides[$m] ?? $defaults[$m] ?? true);
        }

        return self::$cache[$cacheKey] = $map;
    }

    /** @return array<string, ?bool> exceções do paciente (null = segue o padrão). */
    public static function overrides(string $patientId): array
    {
        $rows = DB::table('patient_module_overrides')->where('patient_id', $patientId)->pluck('enabled', 'module')->all();

        return array_map(fn ($m) => array_key_exists($m, $rows) ? (bool) $rows[$m] : null, array_combine(array_keys(self::ALL), array_keys(self::ALL)));
    }

    public static function set(string $module, bool $enabled, string $actor, ?string $actorId = null): void
    {
        DB::transaction(function () use ($module, $enabled, $actor, $actorId) {
            $before = self::enabled($module);

            DB::table('module_settings')->updateOrInsert(
                ['module' => $module],
                ['enabled' => $enabled, 'updated_at' => now(), 'created_at' => now()],
            );
            self::flush();

            Audit::record($actor, 'module.'.($enabled ? 'enable' : 'disable'), "module:$module", null, [
                'from' => $before,
                'to' => $enabled,
            ], $actorId);
        });
    }

    /** Exceção por paciente. $enabled null remove a exceção e o paciente volta ao padrão. */
    public static function setForPatient(string $patientId, string $module, ?bool $enabled, string $actor, ?string $actorId = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($patientId, $module, $enabled, $actor, $actorId, $reason) {
            $before = self::enabled($module, $patientId);

            if ($enabled === null) {
                DB::table('patient_module_overrides')->where(['patient_id' => $patientId, 'module' => $module])->delete();
            } else {
                DB::table('patient_module_overrides')->updateOrInsert(
                    ['patient_id' => $patientId, 'module' => $module],
                    ['enabled' => $enabled, 'reason' => $reason ? mb_substr($reason, 0, 200) : null, 'updated_at' => now(), 'created_at' => now()],
                );
            }
            self::flush();

            Audit::record($actor, 'module.patient.'.($enabled === null ? 'default' : ($enabled ? 'enable' : 'disable')), "module:$module", $patientId, [
                'from' => $before,
                'to' => self::enabled($module, $patientId),
            ], $actorId);
        });
    }

    /** Metas individuais dentro de Metas diárias (configuração do ambiente). */
    public static function goalEnabled(string $goal): bool
    {
        $config = json_decode((string) DB::table('module_settings')->where('module', 'metas')->value('config'), true) ?: [];

        return (bool) ($config[$goal] ?? true);
    }

    public static function setGoal(string $goal, bool $enabled): void
    {
        $config = json_decode((string) DB::table('module_settings')->where('module', 'metas')->value('config'), true) ?: [];
        $config[$goal] = $enabled;
        DB::table('module_settings')->updateOrInsert(['module' => 'metas'], ['config' => json_encode($config), 'updated_at' => now()]);
        Audit::log('module.goal.'.($enabled ? 'enable' : 'disable'), "goal:$goal");
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
