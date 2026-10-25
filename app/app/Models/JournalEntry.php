<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned, SoftDeletes;

    protected $table = 'journal_entries';

    protected array $encrypted = ['mood', 'tags', 'text'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => \App\Casts\DateOnly::class, 'shared' => 'boolean', 'shared_at' => 'datetime'];
    }
}
