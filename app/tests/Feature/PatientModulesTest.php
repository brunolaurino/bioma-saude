<?php

namespace Tests\Feature;

use App\Security\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_modulo_desligado_some_do_app_e_responde_403(): void
    {
        $this->demo();
        $this->asPro('marina@demo.bioma.local');
        $this->post(route('pro.settings.modules'), ['module' => 'saude_mental', 'value' => 0])->assertRedirect();
        Modules::flush();

        $this->asPatient('ana@demo.bioma.local');
        $this->get(route('mental'))->assertForbidden();
        $this->post(route('journal.store'), ['mood' => 3, 'text' => 'teste'])->assertForbidden();
    }

    public function test_excecao_liga_o_modulo_so_para_um_paciente(): void
    {
        $this->demo();
        $ana = $this->patientNamed('Ana');
        $this->asPro('marina@demo.bioma.local');
        $this->post(route('pro.settings.modules'), ['module' => 'saude_mental', 'value' => 0]);
        $this->post(route('pro.patient.modules', $ana), ['module' => 'saude_mental', 'state' => 'on'])->assertRedirect();
        Modules::flush();

        $this->asPatient('ana@demo.bioma.local');
        $this->get(route('mental'))->assertOk();
        $this->asPatient('carlos@demo.bioma.local');
        $this->get(route('mental'))->assertForbidden();
    }
}
