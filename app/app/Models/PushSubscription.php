<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Um aparelho (navegador) que aceitou receber notificações push. */
class PushSubscription extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $hidden = ['endpoint', 'p256dh', 'auth'];

    protected function casts(): array
    {
        return ['last_sent_at' => 'datetime'];
    }
}
