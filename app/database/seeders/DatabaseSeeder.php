<?php

namespace Database\Seeders;

use App\Security\Modules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /** Liga todos os módulos e as regras de alerta no ambiente novo. Não cria pacientes: o ambiente de teste só recebe dados fictícios digitados à mão. */
    public function run(): void
    {
        foreach (array_keys(Modules::ALL) as $module) {
            DB::table('module_settings')->insertOrIgnore([
                'module' => $module, 'enabled' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Regras de alerta ligadas por padrão; "idle" guarda os dias sem registro que geram aviso.
        foreach (array_keys(\App\Domain\AlertEngine::RULES) as $rule) {
            DB::table('alert_rules')->insertOrIgnore(['key' => $rule, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('alert_rules')->insertOrIgnore(['key' => 'idle', 'enabled' => true, 'params' => json_encode(['days' => 7]), 'created_at' => now(), 'updated_at' => now()]);
    }
}
