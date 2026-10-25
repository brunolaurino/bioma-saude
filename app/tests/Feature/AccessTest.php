<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cada profissional da equipe vê só os pacientes e as partes do prontuário liberadas para ele. */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_psicologa_ve_so_os_pacientes_da_equipe_dela(): void
    {
        $this->demo();
        $this->asPro('helena@demo.bioma.local');
        $carlos = $this->patientNamed('Carlos');

        $this->get('/painel/pacientes')->assertOk()->assertSee('Ana Ribeiro')->assertDontSee('Carlos Mendes');
        $this->get(route('pro.patient', $carlos))->assertForbidden();
    }

    public function test_psicologa_nao_ve_bioimpedancia_nem_laudo(): void
    {
        $this->demo();
        $this->asPro('helena@demo.bioma.local');
        $ana = $this->patientNamed('Ana');

        $this->get(route('pro.patient', [$ana, 'aba' => 'desabafos']))->assertOk();
        $page = $this->get(route('pro.patient', [$ana, 'aba' => 'bio']))->assertOk();
        $page->assertDontSee('Registros do paciente');

        $laudo = StoredFile::of($ana)->where('kind', 'laudo')->first();
        if ($laudo) {
            $this->get(route('files.show', $laudo))->assertForbidden();
        }
        $this->post(route('pro.patient.goals', $ana), ['water' => 2000, 'protein' => 100])->assertForbidden();
    }

    public function test_so_o_master_muda_configuracoes_e_ve_auditoria(): void
    {
        $this->demo();
        $this->asPro('julia@demo.bioma.local');
        $this->post(route('pro.settings.modules'), ['module' => 'fotos', 'value' => 0])->assertForbidden();
        $this->get(route('pro.audit'))->assertForbidden();
        $this->get(route('pro.requests'))->assertForbidden();
    }

    public function test_master_tira_profissional_da_equipe_do_paciente(): void
    {
        $this->demo();
        $ana = $this->patientNamed('Ana');
        $helena = \App\Models\User::findByEmail('helena@demo.bioma.local');

        $this->asPro('marina@demo.bioma.local');
        $this->post(route('pro.patient.team', $ana), ['professional_id' => $helena->id, 'modules' => []])->assertRedirect();

        \App\Security\Access::flush();
        $this->asPro('helena@demo.bioma.local');
        $this->get(route('pro.patient', $ana))->assertForbidden();
    }

    public function test_profissional_bloqueado_perde_o_acesso(): void
    {
        $this->demo();
        $rafael = \App\Models\User::findByEmail('rafael@demo.bioma.local');
        $this->asPro('marina@demo.bioma.local');
        $this->post(route('pro.settings.member', $rafael), ['action' => 'block'])->assertRedirect();

        $this->post('/sair');
        $this->post('/entrar', ['email' => 'rafael@demo.bioma.local', 'password' => 'bioma-demo-2026'])->assertSessionHasErrors();
    }
}
