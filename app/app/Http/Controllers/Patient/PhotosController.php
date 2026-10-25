<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Catalog;
use App\Domain\Files;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\StoredFile;
use App\Security\Audit;
use App\Security\Consents;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Fotos de evolução. Exigem consentimento específico (LGPD art. 11, I), revogável a qualquer momento. */
class PhotosController extends Controller
{
    public function create()
    {
        return view('patient.photos-create', ['consent' => Consents::has($this->patient(), 'photos'), 'lead' => Settings::leadName()]);
    }

    public function consent(Request $request)
    {
        $request->validate(['accept' => ['accepted']], ['accept.accepted' => 'Marque a caixa para autorizar as fotos.']);
        Consents::accept($this->patient(), 'photos');

        return redirect()->route('photos.create')->with('status', 'Autorização registrada. Você pode retirar quando quiser em Privacidade.');
    }

    public function store(Request $request)
    {
        $patient = $this->patient();
        abort_unless(Consents::has($patient, 'photos'), 403, 'Autorize as fotos antes de enviar.');
        $request->validate(['foto' => ['required', 'array', 'min:1'], 'foto.*' => ['file']], ['foto.required' => 'Escolha pelo menos uma foto.']);

        $today = Clock::todayIso();
        $saved = 0;
        DB::transaction(function () use ($request, $patient, $today, &$saved) {
            foreach (Catalog::ANGLES as $angle) {
                if (! $request->hasFile("foto.$angle")) {
                    continue;
                }
                // Refazer a foto do mesmo ângulo no mesmo dia substitui a anterior.
                StoredFile::of($patient)->where('kind', 'foto')->where('angle', $angle)->where('taken_on', $today)->get()->each(fn ($f) => Files::purge($f));
                Files::store($request->file("foto.$angle"), $patient, 'foto', ['angle' => $angle, 'taken_on' => $today], "foto.$angle");
                $saved++;
            }
        });

        return redirect()->route('evolution', ['aba' => 'fotos'])->with('status', $saved.' '.($saved > 1 ? 'fotos salvas' : 'foto salva').'.');
    }

    public function compare(Request $request)
    {
        $patient = $this->patient();
        abort_unless(Consents::has($patient, 'photos'), 403);
        $sessions = EvolutionController::photoSessions($patient->id);
        if ($sessions->count() < 2) {
            return redirect()->route('evolution', ['aba' => 'fotos'])->with('error', 'Você precisa de fotos em pelo menos duas datas para comparar.');
        }

        return view('patient.photos-compare', self::compareData($sessions, (new PatientData($patient))->bio(), $request));
    }

    public static function compareData($sessions, $bio, Request $request): array
    {
        $dates = $sessions->map(fn ($s) => $s['date']->toDateString())->all();
        $a = in_array($request->query('antes'), $dates, true) ? $request->query('antes') : end($dates);
        $b = in_array($request->query('depois'), $dates, true) ? $request->query('depois') : $dates[0];
        $angle = in_array($request->query('angulo'), Catalog::ANGLES, true) ? $request->query('angulo') : 'Frente';
        $near = fn ($d) => $bio->isEmpty() ? null : $bio->sortBy(fn ($r) => abs($r['d']->diffInDays($d)))->first();
        $sa = $sessions->first(fn ($s) => $s['date']->toDateString() === $a);
        $sb = $sessions->first(fn ($s) => $s['date']->toDateString() === $b);

        return ['sessions' => $sessions, 'a' => $sa, 'b' => $sb, 'angle' => $angle, 'ba' => $near($sa['date']), 'bb' => $near($sb['date'])];
    }

    public function destroy(string $date)
    {
        $patient = $this->patient();
        $day = Clock::parseDate($date);
        abort_unless($day, 404);
        $files = StoredFile::of($patient)->where('kind', 'foto')->where('taken_on', $day->toDateString())->get();
        abort_if($files->isEmpty(), 404);
        $files->each(fn ($f) => Files::purge($f));
        Audit::log('photo.delete', 'photos:'.$day->toDateString(), $patient->id, ['count' => $files->count()]);

        return redirect()->route('evolution', ['aba' => 'fotos'])->with('status', 'Fotos apagadas.');
    }
}
