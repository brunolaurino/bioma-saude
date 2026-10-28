<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Security\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_desabafo_privado_nunca_chega_ao_painel(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $this->post(route('journal.store'), ['mood' => 2, 'text' => 'Texto privado de teste xyz', 'shared' => 0])->assertRedirect();

        $this->asPro('marina@demo.bioma.local');
        $ana = $this->patientNamed('Ana');
        $this->get(route('pro.patient', [$ana, 'aba' => 'desabafos']))->assertOk()->assertDontSee('Texto privado de teste xyz');
        $this->get(route('pro.journals', ['filtro' => 'todos']))->assertDontSee('Texto privado de teste xyz');

        $private = JournalEntry::of($ana)->where('shared', false)->latest()->first();
        $this->post(route('pro.journals.note', $private))->assertNotFound();
    }

    public function test_banco_guarda_dados_de_saude_cifrados(): void
    {
        $this->demo();
        $dump = '';
        foreach (['patients', 'journal_entries', 'symptom_entries', 'bioimpedance_records', 'clinical_notes', 'users', 'alerts'] as $t) {
            $dump .= json_encode(DB::table($t)->get(), JSON_UNESCAPED_UNICODE);
        }
        $this->assertStringNotContainsString('Ana Ribeiro', $dump);
        $this->assertStringNotContainsString('ana@demo.bioma.local', $dump);
        $this->assertStringNotContainsString('Dor de cabeça', $dump);
        $this->assertStringContainsString('bc1:', $dump);
    }

    public function test_exportacao_pede_senha_de_novo(): void
    {
        $this->demo();
        $user = \App\Models\User::findByEmail('ana@demo.bioma.local');
        $this->actingAs($user)->withSession(['auth.reauth_at' => 0]);
        $this->post(route('privacy.export'))->assertRedirect(route('reauth'));
    }

    public function test_acesso_ao_prontuario_fica_na_auditoria_e_corrente_integra(): void
    {
        $this->demo();
        $ana = $this->patientNamed('Ana');
        $this->asPro('marina@demo.bioma.local');
        $this->get(route('pro.patient', [$ana, 'aba' => 'notas']))->assertOk();

        $this->assertTrue(DB::table('audit_log')->where('action', 'record.view')->where('patient_id', $ana->id)->exists());
        $this->assertNull(Audit::verify());

        $this->asPatient('ana@demo.bioma.local');
        $this->get(route('privacy.accesses'))->assertOk()->assertSee('Abriu o prontuário');
    }

    public function test_retirar_consentimento_de_fotos_apaga_as_fotos(): void
    {
        $this->demo();
        $ana = $this->patientNamed('Ana');
        $this->assertGreaterThan(0, \App\Models\StoredFile::of($ana)->where('kind', 'foto')->count());
        $this->asPatient('ana@demo.bioma.local');
        $this->post(route('privacy.consent'), ['purpose' => 'photos', 'value' => 0])->assertRedirect();
        $this->assertSame(0, \App\Models\StoredFile::withTrashed()->where('patient_id', $ana->id)->where('kind', 'foto')->count());
    }
}
