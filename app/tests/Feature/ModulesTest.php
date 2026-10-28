<?php

namespace Tests\Feature;

use App\Security\Audit;
use App\Security\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_desliga_modulo_e_auditoria_registra(): void
    {
        $this->seed();

        $this->artisan('bioma:modulos', ['acao' => 'desligar', 'modulo' => 'fotos'])->assertSuccessful();

        $this->assertFalse(Modules::enabled('fotos'));
        $this->assertTrue(Modules::enabled('sintomas'));
        $this->assertSame('module.disable', DB::table('audit_log')->value('action'));
        $this->assertNull(Audit::verify());
    }

    public function test_excecao_do_paciente_vence_o_padrao(): void
    {
        $this->seed();
        Modules::set('saude_mental', false, 'master');
        DB::table('patient_module_overrides')->insert([
            'patient_id' => '01JBIOMATESTE000000000000', 'module' => 'saude_mental', 'enabled' => true,
        ]);

        $this->assertTrue(Modules::enabled('saude_mental', '01JBIOMATESTE000000000000'));
        $this->assertFalse(Modules::enabled('saude_mental'));
    }

    public function test_adulteracao_quebra_a_corrente(): void
    {
        Audit::record('master', 'module.disable', 'module:fotos');
        Audit::record('master', 'module.enable', 'module:fotos');

        DB::table('audit_log')->where('id', 1)->update(['actor' => 'outra pessoa']);

        $this->assertSame(1, Audit::verify());
    }
}
