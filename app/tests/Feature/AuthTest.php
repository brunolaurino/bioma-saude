<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_senha_errada_nao_entra_e_nao_revela_se_o_email_existe(): void
    {
        $this->demo();
        $a = $this->post('/entrar', ['email' => 'ana@demo.bioma.local', 'password' => 'errada-123456'])->assertSessionHasErrors();
        $b = $this->post('/entrar', ['email' => 'ninguem@demo.bioma.local', 'password' => 'errada-123456'])->assertSessionHasErrors();
        $this->assertSame(session('errors')->first(), $b->getSession()->get('errors')->first());
        $this->assertGuest();
    }

    public function test_paciente_entra_e_vai_para_o_inicio(): void
    {
        $this->demo();
        $this->post('/entrar', ['email' => 'ana@demo.bioma.local', 'password' => 'bioma-demo-2026'])->assertRedirect(route('home'));
        $this->get('/inicio')->assertOk();
    }

    public function test_profissional_precisa_do_codigo_do_autenticador(): void
    {
        $this->demo();
        $this->post('/entrar', ['email' => 'marina@demo.bioma.local', 'password' => 'bioma-demo-2026'])->assertRedirect(route('2fa.challenge'));
        $this->get('/painel')->assertRedirect(route('2fa.challenge'));
        $this->post('/2fa', ['code' => '000000'])->assertSessionHasErrors();
        $this->get('/painel')->assertRedirect(route('2fa.challenge'));
    }

    public function test_codigo_correto_libera_o_painel(): void
    {
        $this->demo();
        $user = User::findByEmail('marina@demo.bioma.local');
        $this->post('/entrar', ['email' => 'marina@demo.bioma.local', 'password' => 'bioma-demo-2026']);
        $code = (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($user->two_factor_secret);
        $this->post('/2fa', ['code' => $code])->assertRedirect();
        $this->get('/painel')->assertOk();
    }

    public function test_paciente_nao_entra_no_painel_e_profissional_nao_usa_o_app(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $this->get('/painel')->assertRedirect(route('home'));

        $this->asPro('marina@demo.bioma.local');
        $this->get('/inicio')->assertRedirect(route('pro.dashboard'));
    }

    public function test_senha_guardada_com_argon2id(): void
    {
        $this->demo();
        $hash = DB::table('users')->where('email_hash', \App\Security\BlindIndex::email('ana@demo.bioma.local'))->value('password');
        $this->assertStringStartsWith('$argon2id$', $hash);
    }
}
