<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;

abstract class Controller
{
    protected function user(): User
    {
        return auth()->user();
    }

    /** Paciente logado (só nas rotas do app). */
    protected function patient(): Patient
    {
        return auth()->user()->patient;
    }

    /** Registro precisa ser do paciente logado. Responde 404 para não revelar que existe. */
    protected function own(object $model): void
    {
        abort_unless($model->patient_id === $this->patient()->id, 404);
    }
}
