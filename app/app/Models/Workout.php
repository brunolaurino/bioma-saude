<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workout extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned, SoftDeletes;

    protected $table = 'workouts';

    protected array $encrypted = ['note'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => \App\Casts\DateOnly::class];
    }
}
