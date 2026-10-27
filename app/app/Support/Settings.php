<?php

namespace App\Support;

use App\Security\Audit;
use Illuminate\Support\Facades\DB;

/** Dados do ambiente que o master configura: consultório, controlador, encarregado, metas padrão. */
class Settings
{
    public const DEFAULTS = [
        'clinic_name' => 'Consultório',
        'clinic_city' => '',
        'clinic_contact' => '',
        'clinic_hours' => 'seg a sex, 8h às 18h',
        'controller_name' => '',
        'controller_document' => '',
        'dpo_name' => '',
        'dpo_contact' => '',
        'operator_name' => 'Saúde App Tecnologia',
        'operator_document' => '',
        'water_ml_per_kg' => '35',
        'protein_g_per_kg' => '1,4',
        'bio_interval_days' => '30',
        'setup_completed' => '0',
    ];

    private static ?array $cache = null;

    public static function get(string $key): string
    {
        self::$cache ??= DB::table('environment_settings')->pluck('value', 'key')->all();

        return (string) (self::$cache[$key] ?? self::DEFAULTS[$key] ?? '');
    }

    public static function set(array $values): void
    {
        $changed = [];
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            if (self::get($key) !== (string) $value) {
                $changed[] = $key;
            }
            DB::table('environment_settings')->updateOrInsert(['key' => $key], ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]);
        }
        self::$cache = null;

        if ($changed) {
            Audit::log('settings.update', 'settings', null, ['keys' => $changed]);
        }
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** Nome do profissional principal, usado nos textos do app ("a Dra. Marina já pode ver"). */
    public static function leadName(): string
    {
        return (string) DB::table('professionals')->where('role', 'master')->orderBy('created_at')->value('name') ?: 'sua equipe';
    }
}
