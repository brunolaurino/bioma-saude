<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\PrivacyController;
use App\Models\AppNotification;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use App\Models\SecurityIncident;
use App\Security\Audit;
use App\Support\AuditLabels;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Telas de LGPD do master: trilha de auditoria, pedidos de titulares e registro de incidentes. */
class ComplianceController extends Controller
{
    public function audit(Request $request)
    {
        $q = DB::table('audit_log')->orderByDesc('id');
        if ($pid = $request->query('paciente')) {
            $q->where('patient_id', $pid);
        }
        if ($who = $request->query('quem')) {
            $q->where('actor_id', $who);
        }
        $rows = $q->paginate(50)->withQueryString();
        $patients = Patient::whereIn('id', collect($rows->items())->pluck('patient_id')->filter()->unique())->get()->keyBy('id');
        $broken = $request->boolean('verificar') ? (Audit::verify() ?? 0) : null;
        if ($request->boolean('verificar')) {
            Audit::log('audit.verify', 'audit_log', null, ['ok' => $broken === 0]);
        }

        return view('pro.audit', [
            'rows' => $rows, 'patients' => $patients, 'broken' => $broken, 'label' => fn ($a) => AuditLabels::label($a),
            'people' => DB::table('professionals')->orderBy('name')->pluck('name', 'user_id'),
            'patientFilter' => $pid ? Patient::find($pid) : null, 'who' => $who,
        ]);
    }

    public function requests(Request $request)
    {
        $open = $request->query('ver') !== 'fechados';
        $list = DataSubjectRequest::with('patient')->when($open, fn ($q) => $q->where('status', 'aberto'), fn ($q) => $q->where('status', '!=', 'aberto'))
            ->orderBy($open ? 'due_at' : 'closed_at', $open ? 'asc' : 'desc')->limit(200)->get();

        return view('pro.requests', ['list' => $list, 'open' => $open, 'types' => PrivacyController::REQUEST_TYPES]);
    }

    public function closeRequest(Request $request, DataSubjectRequest $dsr)
    {
        abort_unless($dsr->status === 'aberto', 422, 'Este pedido já foi respondido.');
        $data = $request->validate(['status' => ['required', Rule::in(['atendido', 'negado'])], 'response' => ['required', 'string', 'min:10', 'max:5000']], ['response.*' => 'Escreva a resposta que o paciente vai receber.']);
        $dsr->status = $data['status'];
        $dsr->response = trim($data['response']);
        $dsr->closed_by = $this->user()->id;
        $dsr->closed_at = now();
        $dsr->save();
        Audit::log('dsr.close', 'dsr:'.$dsr->id, $dsr->patient_id, ['status' => $data['status']]);
        if ($dsr->patient?->user_id && $dsr->patient->user?->status === 'active') {
            AppNotification::send($dsr->patient->user_id, 'shield-halved', 'Seu pedido sobre dados pessoais foi respondido.', route('privacy'));
        }

        return back()->with('status', 'Pedido respondido.');
    }

    public function incidents()
    {
        return view('pro.incidents', ['list' => SecurityIncident::orderByDesc('detected_at')->get(), 'names' => DB::table('professionals')->pluck('name', 'user_id')]);
    }

    public function storeIncident(Request $request)
    {
        $data = $request->validate([
            'detected_on' => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'affected_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'risk' => ['required', Rule::in(['avaliar', 'baixo', 'relevante'])],
            'anpd_notified' => ['nullable', 'boolean'],
            'patients_notified' => ['nullable', 'boolean'],
        ], ['description.*' => 'Descreva o que aconteceu (sem colar dados de pacientes).']);
        $day = Clock::parseDate($data['detected_on']);
        if (! $day || $day->isFuture()) {
            return back()->withErrors(['detected_on' => 'Use uma data até hoje, no formato dd/mm/aaaa.'])->withInput();
        }
        $i = new SecurityIncident([
            'detected_at' => $day->startOfDay()->utc(), 'affected_count' => $data['affected_count'], 'risk' => $data['risk'],
            'anpd_notified_at' => ! empty($data['anpd_notified']) ? now() : null,
            'patients_notified_at' => ! empty($data['patients_notified']) ? now() : null,
            'recorded_by' => $this->user()->id,
        ]);
        $i->description = trim($data['description']);
        $i->save();
        Audit::log('incident.create', 'incident:'.$i->id, null, ['risk' => $data['risk']]);

        return back()->with('status', 'Incidente registrado.'.($data['risk'] === 'relevante' ? ' Risco relevante: comunique a ANPD e os titulares em até 3 dias úteis.' : ''));
    }
}
