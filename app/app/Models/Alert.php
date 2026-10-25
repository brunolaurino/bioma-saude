<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class Alert extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'alerts';

    protected array $encrypted = ['summary'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => \App\Casts\DateOnly::class, 'resolved_at' => 'datetime'];
    }
}
