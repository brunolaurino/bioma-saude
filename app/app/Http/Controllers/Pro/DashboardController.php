<?php

namespace App\Http\Controllers\Pro;

use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Security\Access;
use App\Security\Modules;
use App\Support\Clock;

class DashboardController extends Controller
{
    public function index()
    {
        $user = $this->user();
        $data = new ProData($user);
        $patients = $data->patients();
        $alerts = $data->openAlerts($patients);
        $unread = $data->unreadJournals($patients);
        $rows = $patients->where('status', 'active')->map(fn ($p) => $data->row($p, $alerts, $unread));
        $today = Clock::today();

        $appointments = Modules::enabled('agenda')
            ? Appointment::whereIn('patient_id', $patients->pluck('id'))
                ->whereBetween('starts_at', [$today->startOfDay()->utc(), $today->endOfDay()->utc()])
                ->where('status', '!=', 'cancelada')->orderBy('starts_at')->get()
            : collect();
        $next = $appointments->first(fn ($a) => $a->status === 'agendada' && $a->starts_at->isFuture());
        $newThisMonth = $patients->where('status', 'active')->filter(fn ($p) => $p->started_on->gte($today->startOfMonth()))->count();
        $hour = (int) Clock::now()->format('G');

        return view('pro.dashboard', [
            'greeting' => ($hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite')).', '.$user->professional->name,
            'rows' => $rows, 'byId' => $patients->keyBy('id'), 'alerts' => $alerts, 'unread' => $unread,
            'appointments' => $appointments, 'next' => $next, 'newThisMonth' => $newThisMonth,
            'idle' => $rows->filter(fn ($r) => $r['idle'])->values(),
        ]);
    }
}
