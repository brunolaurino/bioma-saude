<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class DataSubjectRequest extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'data_subject_requests';

    protected array $encrypted = ['details', 'response'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_at' => 'immutable_datetime', 'closed_at' => 'datetime'];
    }
}
