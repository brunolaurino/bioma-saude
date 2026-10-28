<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoredFile extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned, SoftDeletes;

    protected $table = 'files';

    protected array $encrypted = ['name', 'label'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['taken_on' => \App\Casts\DateOnly::class, 'is_current' => 'boolean'];
    }
}
