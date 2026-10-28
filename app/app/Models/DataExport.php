<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class DataExport extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'data_exports';

    protected array $encrypted = [];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'downloaded_at' => 'datetime'];
    }
}
