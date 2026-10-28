<?php

namespace App\Http\Controllers;

use App\Domain\Files;
use App\Models\StoredFile;
use App\Security\Access;
use App\Security\Audit;
use Illuminate\Http\Request;

/** Entrega um arquivo decifrado para o paciente dono ou para quem da equipe tem o módulo liberado. */
class FileController extends Controller
{
    private const MODULE = ['laudo' => 'bioimpedancia', 'foto' => 'fotos', 'exame' => 'exames', 'plano' => 'nutricao'];

    public function show(Request $request, StoredFile $file)
    {
        $user = $request->user();
        $patient = $file->patient()->withTrashed()->first();
        abort_unless($patient, 404);

        // Fotos corporais exigem o consentimento vigente também para a equipe ver.
        Access::authorizeView($user, $patient, self::MODULE[$file->kind] ?? 'resumo');
        if ($file->kind === 'foto' && $user->isProfessional()) {
            abort_unless(\App\Security\Consents::has($patient, 'photos'), 403, 'O paciente retirou o consentimento das fotos.');
        }

        if ($user->isProfessional()) {
            Audit::log('file.view', 'file:'.$file->id, $patient->id, ['kind' => $file->kind]);
        }

        return Files::response($file, $request->boolean('ver'));
    }
}
