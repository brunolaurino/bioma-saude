<?php

namespace App\Providers;

use App\Security\Access;
use App\Security\KeyVault;
use App\Security\Modules;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Chaves abertas e caches valem só para a requisição atual.
        $this->app->terminating(function () {
            KeyVault::forget();
            Modules::flush();
            Access::flush();
            Settings::flush();
            \App\Domain\AlertEngine::flush();
        });

        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(config('bioma.login.per_minute'))->by('ip|'.$r->ip()),
        ]);
        RateLimiter::for('sensitive', fn (Request $r) => Limit::perMinute(10)->by(($r->user()?->id ?? 'guest').'|'.$r->ip()));
        foreach ([
            'record' => \App\Models\BioRecord::class, 'entry' => \App\Models\JournalEntry::class,
            'session' => \App\Models\TherapySession::class, 'file' => \App\Models\StoredFile::class,
            'patient' => \App\Models\Patient::class, 'alert' => \App\Models\Alert::class,
            'appointment' => \App\Models\Appointment::class, 'dsr' => \App\Models\DataSubjectRequest::class,
            'user' => \App\Models\User::class,
        ] as $param => $model) {
            Route::model($param, $model);
        }

        // Menu do app do paciente: módulos ligados, notificações não lidas e metas que faltam hoje.
        View::composer(['layouts.patient', 'patient.*'], function ($view) {
            $nav = request()->attributes->get('bioma.nav');
            if ($nav === null) {
                $patient = auth()->user()?->patient;
                $nav = ['m' => Modules::map($patient?->id), 'unread' => 0, 'remaining' => 0];
                if ($patient) {
                    $nav['unread'] = \App\Models\AppNotification::where('user_id', $patient->user_id)->whereNull('read_at')->count();
                    $data = new \App\Domain\PatientData($patient);
                    $nav['remaining'] = max(0, count($data->activeGoals()) - $data->doneCount($data->day(\App\Support\Clock::today())));
                }
                request()->attributes->set('bioma.nav', $nav);
            }
            $view->with('nav', $nav);
        });

        // Contadores do menu do painel: alertas abertos, desabafos novos e pedidos LGPD pendentes.
        View::composer(['layouts.pro', 'pro.*', 'shared.*'], function ($view) {
            $user = auth()->user();
            if (! $user?->isProfessional()) {
                return;
            }
            $pro = request()->attributes->get('bioma.pronav');
            if ($pro === null) {
                $data = new \App\Domain\ProData($user);
                $patients = $data->patients();
                $pro = [
                    'alerts' => $data->openAlerts($patients)->count(),
                    'journals' => $data->unreadJournals($patients)->count(),
                    'requests' => $user->isMaster() ? \App\Models\DataSubjectRequest::where('status', 'aberto')->count() : 0,
                    'agenda' => Modules::enabled('agenda'),
                    'me' => $user->professional,
                    'master' => $user->isMaster(),
                ];
                request()->attributes->set('bioma.pronav', $pro);
            }
            $view->with('proNav', $pro);
        });

        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?? $r->ip()));
    }
}
