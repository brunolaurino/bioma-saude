<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BioRecord extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned, SoftDeletes;

    protected $table = 'bioimpedance_records';

    protected array $encrypted = ['values'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['measured_on' => \App\Casts\DateOnly::class];
    }
}
