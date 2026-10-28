<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Entrega uma notificação push em todos os aparelhos de um usuário. */
class SendPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $userId, public array $payload) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user || $user->status !== 'active') {
            return;
        }
        $subs = PushSubscription::where('user_id', $this->userId)->get();
        if ($subs->isEmpty()) {
            return;
        }

        $push = new WebPush(['VAPID' => [
            'subject' => config('bioma.push.subject'),
            'publicKey' => config('bioma.push.public_key'),
            'privateKey' => config('bioma.push.private_key'),
        ]], ['TTL' => 6 * 3600, 'urgency' => 'normal', 'topic' => 'saude-app']);

        $body = json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        foreach ($subs as $s) {
            $push->queueNotification(Subscription::create([
                'endpoint' => $s->endpoint,
                'keys' => ['p256dh' => $s->p256dh, 'auth' => $s->auth],
                'contentEncoding' => 'aes128gcm',
            ]), $body);
        }

        foreach ($push->flush() as $report) {
            $hash = hash('sha256', $report->getEndpoint());
            if ($report->isSuccess()) {
                PushSubscription::where('endpoint_hash', $hash)->update(['last_sent_at' => now()]);
            } elseif ($report->isSubscriptionExpired()) {
                // O aparelho desinstalou o app ou revogou a permissão: esquece o endereço.
                PushSubscription::where('endpoint_hash', $hash)->delete();
            }
        }
    }
}
