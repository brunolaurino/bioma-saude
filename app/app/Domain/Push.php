<?php

namespace App\Domain;

use App\Jobs\SendPush;
use App\Models\PushSubscription;
use App\Models\User;

/**
 * Notificações push pelo navegador (Web Push com chaves VAPID). A mensagem aparece na tela
 * bloqueada, então nunca leva dado de saúde: só lembretes genéricos ou o aviso padrão abaixo.
 */
class Push
{
    public const GENERIC = 'Você tem uma nova notificação.';

    public static function enabled(): bool
    {
        return (bool) (config('bioma.push.public_key') && config('bioma.push.private_key'));
    }

    /** Envia para todos os aparelhos do usuário, fora da requisição (fila). */
    public static function toUser(string $userId, ?string $text, ?string $link = null): void
    {
        if (! self::enabled() || ! PushSubscription::where('user_id', $userId)->exists()) {
            return;
        }
        SendPush::dispatch($userId, self::payload($text, $link));
    }

    /** Monta a mensagem. Sem texto liberado, vai o aviso genérico. Links só do próprio app. */
    public static function payload(?string $text, ?string $link): array
    {
        $path = $link ? (parse_url($link, PHP_URL_PATH) ?: '/') : '/';
        $query = $link ? parse_url($link, PHP_URL_QUERY) : null;

        return [
            'title' => 'Saúde App',
            'body' => $text !== null && $text !== '' ? mb_substr($text, 0, 120) : self::GENERIC,
            'url' => $path.($query ? '?'.$query : ''),
        ];
    }

    public static function subscribe(User $user, string $endpoint, string $p256dh, string $auth, ?string $device): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            ['user_id' => $user->id, 'endpoint' => $endpoint, 'p256dh' => $p256dh, 'auth' => $auth, 'device' => $device ? mb_substr($device, 0, 80) : null],
        );
    }

    public static function unsubscribe(User $user, ?string $endpoint = null): int
    {
        $q = PushSubscription::where('user_id', $user->id);
        if ($endpoint !== null) {
            $q->where('endpoint_hash', hash('sha256', $endpoint));
        }

        return $q->delete();
    }
}
