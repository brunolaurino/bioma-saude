<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Indicators;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\TherapySession;
use App\Security\Modules;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $m = Modules::map($patient->id);
        $today = Clock::today();
        $bio = $m['bioimpedancia'] ? $data->bio() : collect();

        $nextAppointment = $m['agenda'] ? Appointment::of($patient)->where('status', 'agendada')->where('starts_at', '>=', $today->startOfDay()->utc())->orderBy('starts_at')->first() : null;
        $nextTherapy = $m['saude_mental'] ? TherapySession::of($patient)->where('status', 'agendada')->where('starts_at', '>=', $today->startOfDay()->utc())->orderBy('starts_at')->first() : null;
        $todayWorkouts = $m['atividade'] ? $data->workouts()->filter(fn ($w) => $w->day->equalTo($today)) : collect();

        $day = $data->day($today);

        return view('patient.home', [
            'patient' => $patient,
            'm' => $m,
            'bio' => $bio,
            'goals' => $data->goals(),
            'active' => $data->activeGoals(),
            'day' => $day,
            'done' => $data->doneCount($day),
            'streak' => $m['metas'] && Modules::goalEnabled('water') ? $data->waterStreak() : 0,
            'todaySymptoms' => $m['sintomas'] ? $data->todaySymptoms() : collect(),
            'nextAppointment' => $nextAppointment,
            'nextTherapy' => $nextTherapy,
            'todayWorkouts' => $todayWorkouts,
            'bioInterval' => (int) Settings::get('bio_interval_days'),
            'showInstall' => ! $request->cookie('bioma_install'),
            'lead' => Settings::leadName(),
            'indicators' => Indicators::ALL,
        ]);
    }

    /** Sem JavaScript, o botão + abre esta página com a mesma grade do painel deslizante. */
    public function quick()
    {
        return view('patient.quick');
    }

    public function dismissInstall()
    {
        return back()->cookie('bioma_install', '1', 60 * 24 * 365, null, null, null, true, false, 'lax');
    }

    public function notifications()
    {
        $items = AppNotification::where('user_id', $this->user()->id)->orderByDesc('created_at')->limit(50)->get();

        return view('patient.notifications', ['items' => $items]);
    }

    public function readAll()
    {
        AppNotification::where('user_id', $this->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
