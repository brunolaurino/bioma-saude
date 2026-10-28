<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Indicators;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\StoredFile;
use App\Security\Consents;
use App\Security\Modules;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Minha evolução: gráficos de bioimpedância, fotos e exames, conforme os módulos ligados. */
class EvolutionController extends Controller
{
    public function index(Request $request)
    {
        $patient = $this->patient();
        $m = Modules::map($patient->id);
        $tabs = array_keys(array_filter(['graf' => $m['bioimpedancia'], 'fotos' => $m['fotos'], 'exames' => $m['exames']]));
        $tab = in_array($request->query('aba'), $tabs, true) ? $request->query('aba') : $tabs[0];

        $view = ['tab' => $tab, 'tabs' => $tabs, 'lead' => Settings::leadName()];

        if ($tab === 'graf') {
            $data = new PatientData($patient);
            $view['charts'] = self::charts($data, $request);
        } elseif ($tab === 'fotos') {
            $view['consent'] = Consents::has($patient, 'photos');
            $view['sessions'] = self::photoSessions($patient->id);
        } else {
            $view['exams'] = StoredFile::of($patient)->whereIn('kind', ['exame', 'laudo'])->orderByDesc('taken_on')->orderByDesc('created_at')->get();
        }

        return view('patient.evolution', $view);
    }

    /** Dados do painel de gráficos, compartilhado com o prontuário do profissional. */
    public static function charts(PatientData $data, Request $request): array
    {
        $bio = $data->bio();
        $k = array_key_exists((string) $request->query('ind'), Indicators::ALL) ? $request->query('ind') : 'w';
        $period = in_array($request->query('periodo'), ['3m', '6m', 'all'], true) ? $request->query('periodo') : 'all';
        $slice = match ($period) {
            '3m' => $bio->slice(-4)->values(),
            '6m' => $bio->slice(-7)->values(),
            default => $bio,
        };

        return ['bio' => $slice, 'all' => $bio, 'k' => $k, 'ind' => Indicators::get($k), 'period' => $period, 'goal' => $k === 'w' ? $data->goals()['weight'] : null];
    }

    /** Fotos agrupadas por data: [['date' => ..., 'imgs' => ['Frente' => StoredFile, ...]], ...] mais recentes primeiro. */
    public static function photoSessions(string $patientId)
    {
        return StoredFile::of($patientId)->where('kind', 'foto')->orderByDesc('taken_on')->get()
            ->groupBy(fn ($f) => $f->taken_on->toDateString())
            ->map(fn ($g, $d) => ['date' => $g->first()->taken_on, 'imgs' => $g->keyBy('angle')])
            ->values();
    }
}
