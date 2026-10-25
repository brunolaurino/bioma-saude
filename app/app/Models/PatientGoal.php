<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class PatientGoal extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'patient_goals';

    protected array $encrypted = ['target'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }
}
