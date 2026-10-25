<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class Appointment extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'appointments';

    protected array $encrypted = [];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime'];
    }
}
