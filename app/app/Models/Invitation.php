<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class Invitation extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'invitations';

    protected array $encrypted = [];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'used_at' => 'datetime'];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
