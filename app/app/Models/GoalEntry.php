<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class GoalEntry extends Model
{
    use HasEncryptedFields, HasUlids, PatientOwned;

    protected $table = 'goal_entries';

    protected array $encrypted = ['value'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => \App\Casts\DateOnly::class];
    }
}
