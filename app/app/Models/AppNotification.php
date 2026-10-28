<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Notificação dentro do app. O texto é sempre genérico, sem dado de saúde. */
class AppNotification extends Model
{
    use HasUlids;

    protected $table = 'notifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * Cria a notificação do sino e avisa os aparelhos com push. O push leva $pushText só quando
     * quem chama garante que o texto é genérico (lembretes); senão vai o aviso padrão.
     */
    public static function send(string $userId, string $icon, string $text, ?string $link = null, ?string $pushText = null): self
    {
        $n = self::create(['user_id' => $userId, 'icon' => $icon, 'text' => $text, 'link' => $link]);
        \App\Domain\Push::toUser($userId, $pushText, $link);

        return $n;
    }
}
