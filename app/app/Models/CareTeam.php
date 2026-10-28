<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Vínculo de um profissional da equipe com um paciente, com os módulos que ele pode ver. */
class CareTeam extends Model
{
    protected $table = 'care_team';

    public $incrementing = false;

    protected $primaryKey = 'patient_id';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['modules' => 'array'];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id', 'user_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Chave composta: salva sempre por patient_id + professional_id.
    protected function setKeysForSaveQuery($query): Builder
    {
        return $query->where('patient_id', $this->getAttribute('patient_id'))->where('professional_id', $this->getAttribute('professional_id'));
    }
}
