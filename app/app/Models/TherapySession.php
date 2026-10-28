<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class TherapySession extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'therapy_sessions';

    protected array $encrypted = [];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime'];
    }
}
