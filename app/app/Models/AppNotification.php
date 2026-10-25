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

    public static function send(string $userId, string $icon, string $text, ?string $link = null): self
    {
        return self::create(['user_id' => $userId, 'icon' => $icon, 'text' => $text, 'link' => $link]);
    }
}
