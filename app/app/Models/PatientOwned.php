<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Registros que pertencem a um paciente. A autorização e a cifra partem do patient_id. */
trait PatientOwned
{
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeOf(Builder $query, Patient|string $patient): Builder
    {
        return $query->where('patient_id', $patient instanceof Patient ? $patient->getKey() : $patient);
    }
}
