<?php

namespace Tests\Feature;

use App\Domain\Push;
use App\Jobs\SendPush;
use App\Models\AppNotification;
use App\Models\PushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Notificações push: assinatura por aparelho e mensagem sempre genérica (LGPD). */
class PushTest extends TestCase
{
    use RefreshDatabase;

    private const SUB = ['endpoint' => 'https://push.example.com/send/abc123', 'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM', 'auth' => 'tBHItJI5svbpez7KI4CCXg'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['bioma.push.public_key' => 'chave-publica-de-teste', 'bioma.push.private_key' => 'chave-privada-de-teste']);
    }

    public function test_paciente_ativa_e_desativa_no_aparelho(): void
    {
        $this->demo();
        $user = $this->asPatient('ana@demo.bioma.local');

        $this->get(route('profile'))->assertOk()->assertSee('Notificações no celular');
        $this->post(route('push.subscribe'), self::SUB)->assertRedirect()->assertSessionHas('status', 'Notificações ativadas neste aparelho.');
        $this->post(route('push.subscribe'), self::SUB)->assertRedirect();
        $this->assertSame(1, PushSubscription::where('user_id', $user->id)->count());

        $this->post(route('push.unsubscribe'), ['endpoint' => self::SUB['endpoint']])->assertRedirect();
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_recusa_endereco_que_nao_e_https(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $this->post(route('push.subscribe'), ['endpoint' => 'http://push.example.com/x'] + self::SUB)->assertSessionHasErrors('endpoint');
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_aviso_vai_com_texto_generico_e_lembrete_com_o_proprio_texto(): void
    {
        Queue::fake();
        $this->demo();
        $user = $this->asPatient('ana@demo.bioma.local');
        Push::subscribe($user, self::SUB['endpoint'], self::SUB['p256dh'], self::SUB['auth'], 'Android · Chrome');

        AppNotification::send($user->id, 'flask', 'Seu exame de glicemia saiu: 180 mg/dL', route('evolution'));
        Queue::assertPushed(SendPush::class, fn ($job) => $job->payload['body'] === Push::GENERIC && $job->payload['url'] === '/evolucao');

        AppNotification::send($user->id, 'glass-water', 'Hora de beber água', route('home'), 'Hora de beber água');
        Queue::assertPushed(SendPush::class, fn ($job) => $job->payload['body'] === 'Hora de beber água');
    }

    public function test_sem_chaves_nao_mostra_nem_envia(): void
    {
        Queue::fake();
        config(['bioma.push.public_key' => null]);
        $this->demo();
        $user = $this->asPatient('ana@demo.bioma.local');
        PushSubscription::create(['user_id' => $user->id, 'endpoint_hash' => hash('sha256', 'x'), 'endpoint' => 'x', 'p256dh' => 'x', 'auth' => 'x']);

        $this->get(route('profile'))->assertOk()->assertDontSee('Notificações no celular');
        AppNotification::send($user->id, 'bell', 'Teste');
        Queue::assertNothingPushed();
    }

    public function test_sair_dos_outros_aparelhos_cancela_as_assinaturas(): void
    {
        $this->demo();
        $user = $this->asPatient('ana@demo.bioma.local');
        Push::subscribe($user, self::SUB['endpoint'], self::SUB['p256dh'], self::SUB['auth'], null);

        $this->post(route('profile.sessions'))->assertRedirect();
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }
}
