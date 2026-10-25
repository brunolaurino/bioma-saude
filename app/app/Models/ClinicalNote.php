<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalNote extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned, SoftDeletes;

    protected $table = 'clinical_notes';

    protected array $encrypted = ['text'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }
}
