<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Catalog;
use App\Domain\Notice;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\CareTeam;
use App\Models\PatientGoal;
use App\Models\TherapySession;
use App\Security\Audit;
use App\Security\Consents;
use App\Security\Modules;
use App\Security\Passwords;
use App\Support\Clock;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $m = Modules::map($patient->id);
        $bio = $data->bio();
        $streak = $m['metas'] ? $data->waterStreak() : 0;
        $journalCount = $data->journal(false)->count();

        $achievements = [];
        if ($m['conquistas']) {
            $lost = $bio->count() > 1 ? $bio->first()['w'] - $bio->last()['w'] : 0;
            $therapy = TherapySession::of($patient)->where('status', 'feita')->count();
            $workouts = $data->workouts()->count();
            $achievements = array_values(array_filter([
                ['seedling', 'Primeiro registro', $bio->isNotEmpty() || $journalCount > 0, null],
                $m['metas'] ? ['glass-water', '7 dias de água', $streak >= 7, min($streak, 7).'/7'] : null,
                $m['bioimpedancia'] ? ['weight-scale', '5 kg a menos', $lost >= 5, Fmt::num(max(0, $lost)).'/5 kg'] : null,
                $m['saude_mental'] ? ['brain', '4 sessões de terapia', $therapy >= 4, min($therapy, 4).'/4'] : null,
                $m['atividade'] ? ['dumbbell', '10 treinos', $workouts >= 10, min($workouts, 10).'/10'] : null,
                $m['saude_mental'] ? ['feather', '5 desabafos', $journalCount >= 5, min($journalCount, 5).'/5'] : null,
            ]));
        }

        return view('patient.profile', [
            'patient' => $patient, 'm' => $m, 'achievements' => $achievements,
            'stats' => array_filter([
                $m['bioimpedancia'] ? ['Registros', $bio->count()] : null,
                $m['metas'] ? ['Sequência', $streak.' '.Fmt::plural($streak, 'dia', 'dias')] : null,
                $m['saude_mental'] ? ['Desabafos', $journalCount] : null,
            ]),
        ]);
    }

    public function data()
    {
        return view('patient.profile-data', ['patient' => $this->patient(), 'email' => $this->user()->email]);
    }

    public function saveData(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]*$/'],
            'birth_date' => ['nullable', 'string', 'max:10'],
            'height' => ['nullable', 'string', 'max:6'],
        ], ['name.*' => 'Digite seu nome completo.', 'phone.regex' => 'Use só números, espaços, parênteses e traço.']);

        $patient = $this->patient();
        $birth = null;
        if (! empty($data['birth_date'])) {
            $d = Clock::parseDate($data['birth_date']);
            if (! $d || $d->isFuture() || $d->year < 1900) {
                return back()->withErrors(['birth_date' => 'Use a data no formato dd/mm/aaaa.'])->withInput();
            }
            $birth = $d->toDateString();
        }
        $height = Fmt::parseNumber($data['height'] ?? null);
        if ($height !== null && ($height < 0.5 || $height > 2.6)) {
            return back()->withErrors(['height' => 'Use metros, por exemplo 1,66.'])->withInput();
        }

        $patient->name = trim($data['name']);
        $patient->phone = $data['phone'] ?: null;
        $patient->birth_date = $birth;
        $patient->height = $height !== null ? Fmt::num($height, 2) : null;
        $patient->save();
        Audit::log('patient.data.update', 'patient:'.$patient->id, $patient->id);

        return redirect()->route('profile')->with('status', 'Dados atualizados.');
    }

    public function goals()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $bio = $data->bio();
        $perKg = (int) Settings::get('water_ml_per_kg');
        $suggest = $bio->isNotEmpty() ? (int) (round($bio->last()['w'] * $perKg / 250) * 250) : null;

        return view('patient.goals', ['goals' => $data->goals(), 'suggest' => $suggest, 'perKg' => $perKg, 'lead' => Settings::leadName(),
            'goalOn' => collect(Modules::GOALS)->map(fn ($l, $g) => Modules::goalEnabled($g))->all()]);
    }

    public function saveGoals(Request $request)
    {
        $request->validate(['water' => ['required', 'integer', 'between:1000,6000']], ['water.*' => 'Escolha entre 1.000 e 6.000 ml.']);
        $patient = $this->patient();
        $goal = PatientGoal::of($patient)->where('type', 'water')->first() ?? new PatientGoal(['patient_id' => $patient->id, 'type' => 'water']);
        $goal->target = (int) (round($request->integer('water') / 250) * 250);
        $goal->set_by = $this->user()->id;
        $goal->save();

        return redirect()->route('habits')->with('status', 'Meta de água atualizada.');
    }

    public function reminders()
    {
        $patient = $this->patient();
        $prefs = DB::table('reminder_prefs')->where('patient_id', $patient->id)->pluck('enabled', 'key');
        $rows = collect(Catalog::REMINDERS)->filter(fn ($r) => Modules::enabled($r['module'], $patient->id))
            ->map(fn ($r, $k) => $r + ['on' => (bool) ($prefs[$k] ?? $r['default'])]);

        return view('patient.reminders', ['rows' => $rows]);
    }

    public function saveReminder(Request $request)
    {
        $request->validate(['key' => ['required', Rule::in(array_keys(Catalog::REMINDERS))], 'enabled' => ['required', 'boolean']]);
        DB::table('reminder_prefs')->updateOrInsert(
            ['patient_id' => $this->patient()->id, 'key' => $request->input('key')],
            ['enabled' => $request->boolean('enabled'), 'updated_at' => now(), 'created_at' => now()],
        );

        return back()->with('status', $request->boolean('enabled') ? 'Lembrete ativado.' : 'Lembrete desativado.');
    }

    public function team()
    {
        $patient = $this->patient();
        $master = \App\Models\Professional::where('role', 'master')->first();
        $team = CareTeam::where('patient_id', $patient->id)->with('professional')->get()->pluck('professional')->filter();
        if ($master) {
            $team = collect([$master])->merge($team->reject(fn ($p) => $p->user_id === $master->user_id));
        }

        return view('patient.team', ['team' => $team]);
    }

    public function sessions(Request $request)
    {
        return view('shared.sessions', [
            'sessions' => self::deviceList($request),
            'action' => route('profile.sessions'),
            'back' => route('profile'),
        ]);
    }

    public function endSessions(Request $request)
    {
        self::endOtherSessions($request);

        return back()->with('status', 'Você saiu dos outros aparelhos.');
    }

    public static function deviceList(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get()
            ->map(fn ($s) => (object) ['current' => $s->id === $request->session()->getId(), 'agent' => self::agent((string) $s->user_agent), 'ip' => $s->ip_address, 'last' => \Carbon\CarbonImmutable::createFromTimestamp($s->last_activity)]);
    }

    public static function endOtherSessions(Request $request): void
    {
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        // Os aparelhos desconectados também param de receber push; este pode ativar de novo no perfil.
        \App\Domain\Push::unsubscribe($request->user());
        Audit::log('auth.sessions.end_others', 'user:'.$request->user()->id);
    }

    public static function agent(string $ua): string
    {
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone', str_contains($ua, 'iPad') => 'iPad', str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS') => 'Mac', str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Linux') => 'Linux', default => 'Aparelho',
        };
        $br = match (true) {
            str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'Chrome') => 'Chrome', str_contains($ua, 'Firefox') => 'Firefox', str_contains($ua, 'Safari') => 'Safari', default => 'navegador',
        };

        return "$br no $os";
    }

    public function password()
    {
        return view('shared.password', ['action' => route('profile.password'), 'back' => route('profile')]);
    }

    public function savePassword(Request $request)
    {
        self::changePassword($request);

        return redirect()->route('profile')->with('status', 'Senha alterada. Os outros aparelhos foram desconectados.');
    }

    public static function changePassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string', 'max:128', 'confirmed']], ['password.required' => 'Digite a nova senha.', 'password.confirmed' => 'As duas senhas não são iguais.']);
        $user = $request->user();
        if ($problem = Passwords::problem($request->input('password'), [$user->email, $user->displayName()])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => $problem]);
        }
        if (Hash::check($request->input('password'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'A nova senha precisa ser diferente da atual.']);
        }
        $user->forceFill(['password' => $request->input('password')])->save();
        self::endOtherSessions($request);
        Audit::log('auth.password.change', 'user:'.$user->id);
    }

    public function help()
    {
        return view('patient.help', ['lead' => Settings::leadName(), 'contact' => Settings::get('clinic_contact'), 'hours' => Settings::get('clinic_hours'), 'm' => Modules::map($this->patient()->id)]);
    }

    public function noticeReview()
    {
        return view('patient.notice-review', ['notice' => Notice::current()]);
    }

    public function noticeAccept(Request $request)
    {
        $request->validate(['accept' => ['accepted']], ['accept.accepted' => 'Confirme que leu o aviso para continuar.']);
        Consents::accept($this->patient(), 'privacy_notice');

        return redirect()->route('home')->with('status', 'Obrigado. Aviso de privacidade confirmado.');
    }
}
