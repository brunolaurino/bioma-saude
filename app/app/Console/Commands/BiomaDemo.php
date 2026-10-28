<?php

namespace App\Console\Commands;

use App\Domain\AlertEngine;
use App\Domain\Files;
use App\Domain\Invites;
use App\Domain\Notice;
use App\Models\Appointment;
use App\Models\BioRecord;
use App\Models\CareTeam;
use App\Models\ClinicalNote;
use App\Models\Consultation;
use App\Models\GoalEntry;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\PatientGoal;
use App\Models\Professional;
use App\Models\SymptomEntry;
use App\Models\TherapySession;
use App\Models\User;
use App\Models\Workout;
use App\Security\Consents;
use App\Security\Modules;
use App\Security\TwoFactor;
use App\Support\Clock;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Popula o ambiente de teste com dados 100% fictícios (nomes inventados, e-mails em
 * demo.bioma.local). Recusa rodar em produção.
 */
class BiomaDemo extends Command
{
    protected $signature = 'bioma:demo {--senha=bioma-demo-2026 : senha de todas as contas fictícias}';

    protected $description = 'Cria consultório, equipe e pacientes fictícios para testar o app';

    private string $password;

    private array $secrets = [];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('bioma:demo não roda em produção.');

            return self::FAILURE;
        }
        if (User::exists()) {
            $this->error('O banco já tem contas. Rode "php artisan migrate:fresh --seed" antes.');

            return self::FAILURE;
        }

        mt_srand(42);
        $this->password = (string) $this->option('senha');
        $this->call('db:seed', ['--force' => true]);

        Settings::set([
            'clinic_name' => 'Clínica Bioma Demonstração', 'clinic_city' => 'São Paulo, SP', 'clinic_contact' => '(11) 4000-0000',
            'controller_name' => 'Marina Albuquerque (fictício)', 'controller_document' => '000.000.000-00',
            'dpo_name' => 'Marina Albuquerque (fictício)', 'dpo_contact' => 'privacidade@demo.bioma.local',
            'operator_document' => '00.000.000/0001-00', 'setup_completed' => '1',
        ]);

        $marina = $this->pro('Dra. Marina Albuquerque', 'marina@demo.bioma.local', 'Medicina', 'master', '123456/SP');
        $helena = $this->pro('Psic. Helena Costa', 'helena@demo.bioma.local', 'Psicologia', 'member', '06/12345');
        $rafael = $this->pro('Prof. Rafael Lima', 'rafael@demo.bioma.local', 'Educação física', 'member', '012345-G/SP');
        $julia = $this->pro('Nutri. Júlia Nunes', 'julia@demo.bioma.local', 'Nutrição', 'member', '3-45678');

        auth()->login($marina);
        Notice::publish(Notice::draft(), true, $marina->id);

        $today = Clock::today();
        $ana = $this->patient('Ana Ribeiro', 'ana@demo.bioma.local', '1988-03-14', '1,66', 'Emagrecimento', 150, [80.8, 33.8, 26.1, 10, 1530], [-1.6, -0.9, 0.3, -0.4, 6], 72.0, 2500, 110);
        $this->team($ana, $helena, ['saude_mental']);
        $this->team($ana, $rafael, ['atividade', 'bioimpedancia', 'metas']);
        $this->team($ana, $julia, ['nutricao', 'metas', 'bioimpedancia', 'sintomas', 'exames']);
        $this->fillDaily($ana, 30, 0.75);
        $this->symptoms($ana, [[0, 'Dor de cabeça', 'Leve'], [2, 'Enjoo', 'Moderada'], [5, 'Cansaço', 'Leve'], [6, 'Cansaço', 'Leve'], [9, 'Insônia', 'Leve'], [1, 'Azia', 'Forte']]);
        $this->workouts($ana, 30);
        $this->journal($ana);
        $this->therapy($ana);
        $this->consults($ana, $marina, $julia);
        $this->photos($ana);
        Appointment::create(['patient_id' => $ana->id, 'professional_id' => $marina->id, 'starts_at' => $this->at($today, 14), 'type' => 'Retorno mensal']);

        $carlos = $this->patient('Carlos Mendes', 'carlos@demo.bioma.local', '1979-11-02', '1,78', 'Saúde metabólica', 120, [98.4, 31.0, 33.2, 15, 1890], [-1.1, -0.6, 0.2, -0.5, 4], 88.0, 3200, 140);
        $this->team($carlos, $rafael, ['atividade', 'bioimpedancia']);
        $this->fillDaily($carlos, 30, 0.55);
        $this->symptoms($carlos, [[1, 'Tontura', 'Moderada'], [3, 'Constipação', 'Leve']]);
        $this->workouts($carlos, 20);
        Appointment::create(['patient_id' => $carlos->id, 'professional_id' => $marina->id, 'starts_at' => $this->at($today, 16), 'type' => 'Retorno']);

        $bia = $this->patient('Beatriz Souza', 'beatriz@demo.bioma.local', '1995-07-21', '1,61', 'Composição corporal', 90, [64.2, 29.5, 22.4, 6, 1360], [-0.4, -0.7, 0.4, 0, 8], 60.0, 2200, 95);
        $this->team($bia, $helena, ['saude_mental']);
        $this->fillDaily($bia, 30, 0.85);
        $this->journal($bia);
        Appointment::create(['patient_id' => $bia->id, 'professional_id' => $marina->id, 'starts_at' => $this->at($today->addDays(2), 10), 'type' => 'Reavaliação']);

        $pedro = $this->patient('Pedro Alves', 'pedro@demo.bioma.local', '1990-01-30', '1,82', 'Ganho de massa muscular', 69, [76.0, 18.2, 34.0, 5, 1750], [0.6, -0.2, 0.5, 0, 10], 80.0, 3000, 150);
        $this->fillDaily($pedro, 30, 0.6, 9);
        // Uma paciente só com convite, para testar o primeiro acesso.
        $luiza = $this->patient('Luiza Martins', 'luiza@demo.bioma.local', '2001-05-09', '1,70', 'Reeducação alimentar', 0, null, null, null, 2400, 90, invited: true);
        $code = Invites::forPatient($luiza, $luiza->user);

        auth()->logout();

        $this->newLine();
        $this->info('Pronto. Ambiente de demonstração criado com dados fictícios.');
        $this->line('Senha de todas as contas: '.$this->password);
        $this->table(['Conta', 'E-mail', 'Chave do 2FA (app autenticador)'], [
            ['Master', 'marina@demo.bioma.local', $this->secrets['marina@demo.bioma.local']],
            ['Psicologia', 'helena@demo.bioma.local', $this->secrets['helena@demo.bioma.local']],
            ['Educação física', 'rafael@demo.bioma.local', $this->secrets['rafael@demo.bioma.local']],
            ['Nutrição', 'julia@demo.bioma.local', $this->secrets['julia@demo.bioma.local']],
            ['Paciente', 'ana@demo.bioma.local', '-'],
            ['Paciente', 'carlos@demo.bioma.local', '-'],
        ]);
        $this->line('Convite de primeiro acesso da Luiza Martins: '.$code);
        $this->newLine();
        $this->line('Profissionais: a chave acima NÃO é o código. Cadastre a chave no app autenticador (Google Authenticator,');
        $this->line('1Password…) e digite os 6 dígitos que ele mostra. Sem celular: php artisan bioma:codigo marina@demo.bioma.local');

        return self::SUCCESS;
    }

    private function pro(string $name, string $email, string $specialty, string $role, string $number): User
    {
        $secret = TwoFactor::newSecret();
        [, $hashes] = TwoFactor::recoveryCodes();
        $user = new User(['type' => 'professional', 'status' => 'active']);
        $user->setEmailAddress($email);
        $user->password = $this->password;
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $hashes, 'two_factor_confirmed_at' => now()]);
        $user->save();
        Professional::create(['user_id' => $user->id, 'name' => $name, 'role' => $role, 'specialty' => $specialty,
            'council' => Professional::SPECIALTIES[$specialty]['council'], 'council_number' => $number]);
        $this->secrets[$email] = $secret;

        return $user;
    }

    /** $base = [peso, gordura, músculo, visceral, TMB] no início; $step = variação média por mês. */
    private function patient(string $name, string $email, string $birth, string $height, string $objective, int $daysIn, ?array $base, ?array $step, ?float $weightGoal, int $water, int $protein, bool $invited = false): Patient
    {
        $today = Clock::today();
        $user = new User(['type' => 'patient', 'status' => $invited ? 'pending' : 'active']);
        $user->setEmailAddress($email);
        if (! $invited) {
            $user->password = $this->password;
        }
        $user->save();

        $patient = new Patient(['user_id' => $user->id, 'started_on' => $today->subDays($daysIn)->toDateString(), 'status' => $invited ? 'invited' : 'active', 'created_by' => auth()->id()]);
        $patient->name = $name;
        $patient->birth_date = $birth;
        $patient->phone = '(11) 90000-'.str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $patient->height = $height;
        $patient->objective = $objective;
        $patient->save();

        foreach (array_filter(['weight' => $weightGoal, 'water' => $water, 'protein' => $protein], fn ($v) => $v !== null) as $type => $target) {
            $g = new PatientGoal(['patient_id' => $patient->id, 'type' => $type, 'set_by' => auth()->id()]);
            $g->target = $target;
            $g->save();
        }

        if (! $invited) {
            Consents::accept($patient, 'privacy_notice');
        }

        if ($base) {
            $months = intdiv($daysIn, 30);
            for ($i = 0; $i <= $months; $i++) {
                $vals = [];
                foreach (['w', 'f', 'm', 'v', 't'] as $k => $key) {
                    $noise = in_array($key, ['v', 't'], true) ? 0 : (mt_rand(-20, 20) / 100);
                    $v = $base[$k] + $step[$k] * $i + $noise;
                    $vals[$key] = $key === 'v' ? (int) round(max(1, $v)) : ($key === 't' ? (int) round($v) : round($v, 1));
                }
                $r = new BioRecord(['patient_id' => $patient->id, 'measured_on' => $today->subDays($daysIn - $i * 30)->toDateString(), 'created_by' => $user->id]);
                $r->values = $vals;
                $r->save();
            }
        }

        return $patient->fresh();
    }

    private function team(Patient $p, User $pro, array $modules): void
    {
        CareTeam::create(['patient_id' => $p->id, 'professional_id' => $pro->id, 'modules' => $modules]);
    }

    private function fillDaily(Patient $p, int $days, float $rate, int $skipLast = 0): void
    {
        $today = Clock::today();
        $water = (int) $p->goal('water', 2500);
        for ($i = $days - 1; $i >= $skipLast; $i--) {
            $d = $today->subDays($i)->toDateString();
            $hit = fn () => mt_rand(0, 100) / 100 < $rate;
            $values = [
                'water' => $i === 0 ? 1250 : ($hit() ? $water : (int) (round($water * mt_rand(40, 90) / 100 / 250) * 250)),
                'protein' => $i === 0 ? null : $hit(),
                'frutas' => $hit(), 'verduras' => $hit(), 'legumes' => $i === 0 ? false : $hit(),
                'feeling' => $i === 0 ? null : true,
            ];
            foreach ($values as $type => $value) {
                if ($value === null) {
                    continue;
                }
                $e = new GoalEntry(['patient_id' => $p->id, 'day' => $d, 'type' => $type]);
                $e->value = $value;
                $e->save();
            }
        }
    }

    private function symptoms(Patient $p, array $list): void
    {
        foreach ($list as [$ago, $name, $int]) {
            $s = new SymptomEntry(['patient_id' => $p->id, 'day' => Clock::today()->subDays($ago)->toDateString(), 'intensity' => $int]);
            $s->symptom = $name;
            $s->note = $int === 'Forte' ? 'Começou depois do almoço e durou a tarde toda.' : null;
            $s->save();
            AlertEngine::onSymptom($s);
        }
    }

    private function workouts(Patient $p, int $days): void
    {
        $plan = [['Força', 'Musculação', 50, 'Moderada'], ['Aeróbico', 'Caminhada', 40, 'Leve'], ['Aeróbico', 'Bicicleta', 30, 'Intensa'], ['Força', 'Funcional', 45, 'Moderada']];
        for ($i = $days - 1; $i >= 0; $i--) {
            if (! in_array(Clock::today()->subDays($i)->dayOfWeek, [1, 3, 5, 6], true)) {
                continue;
            }
            [$cat, $mod, $min, $int] = $plan[$i % 4];
            $w = new Workout(['patient_id' => $p->id, 'day' => Clock::today()->subDays($i)->toDateString(), 'category' => $cat, 'modality' => $mod, 'minutes' => $min, 'intensity' => $int]);
            $w->note = $i % 7 === 0 ? 'Aumentei a carga no agachamento.' : null;
            $w->save();
        }
    }

    private function journal(Patient $p): void
    {
        $items = [
            [1, 4, ['Trabalho', 'Rotina'], 'Semana corrida, mas consegui manter as refeições no horário. Fiquei orgulhosa de não pular o almoço.', true],
            [4, 2, ['Emoções', 'Sono'], 'Dormi mal duas noites seguidas e acabei beliscando à noite. Quero conversar sobre isso na terapia.', true],
            [8, 5, ['Corpo'], 'A calça que não fechava em março serviu hoje. Dia ótimo.', true],
            [11, 3, ['Família'], 'Almoço de família no domingo. Comi mais do que queria, mas sem culpa.', false],
            [15, 1, ['Emoções'], 'Dia muito difícil. Tudo parecia pesado e não tive vontade de sair de casa.', true],
        ];
        foreach ($items as [$ago, $mood, $tags, $text, $shared]) {
            $j = new JournalEntry(['patient_id' => $p->id, 'day' => Clock::today()->subDays($ago)->toDateString(), 'shared' => $shared, 'shared_at' => $shared ? now()->subDays($ago) : null]);
            $j->mood = $mood;
            $j->tags = $tags;
            $j->text = $text;
            $j->save();
            AlertEngine::onJournal($j);
        }
    }

    private function therapy(Patient $p): void
    {
        $today = Clock::today();
        foreach ([[-14, 'feita'], [-7, 'feita'], [0, 'agendada'], [7, 'agendada']] as [$off, $st]) {
            TherapySession::create(['patient_id' => $p->id, 'starts_at' => $this->at($today->addDays($off + 1), 19), 'status' => $st]);
        }
    }

    private function consults(Patient $p, User $marina, User $julia): void
    {
        $today = Clock::today();
        foreach ([[150, $marina, 'Primeira consulta', 'Objetivo: perder 8 kg com preservação de massa magra. Início do plano.'], [120, $julia, 'Retorno', 'Ajuste de proteína para 110 g. Boa adesão.'], [90, $marina, 'Retorno mensal', 'Queda consistente de peso. Mantida conduta.'], [60, $julia, 'Retorno', 'Incluído lanche da tarde com proteína.'], [30, $marina, 'Retorno mensal', 'Relata enjoo leve em alguns dias. Orientada a registrar no app.']] as [$ago, $pro, $type, $note]) {
            $c = new Consultation(['patient_id' => $p->id, 'professional_id' => $pro->id, 'day' => $today->subDays($ago)->toDateString(), 'type' => $type]);
            $c->note = $note;
            $c->save();
        }
        $n = new ClinicalNote(['patient_id' => $p->id, 'author_id' => $marina->id]);
        $n->text = 'Na próxima consulta: revisar sono e o enjoo dos últimos dias. Avaliar troca de horário da medicação.';
        $n->save();
    }

    /** Fotos fictícias: silhuetas desenhadas com GD, nunca imagens de pessoas reais. */
    private function photos(Patient $p): void
    {
        Consents::accept($p, 'photos');
        foreach ([[120, 1.12], [0, 1.0]] as [$ago, $scale]) {
            foreach (['Frente', 'Lado', 'Costas'] as $angle) {
                $tmp = tempnam(sys_get_temp_dir(), 'demo').'.png';
                $img = imagecreatetruecolor(600, 800);
                imagefill($img, 0, 0, imagecolorallocate($img, 232, 240, 234));
                $c = imagecolorallocate($img, 120, 150, 130);
                $w = (int) (($angle === 'Lado' ? 120 : 200) * $scale);
                imagefilledellipse($img, 300, 150, 110, 130, $c);
                imagefilledellipse($img, 300, 420, $w, 420, $c);
                imagefilledrectangle($img, 300 - (int) ($w * 0.35), 560, 300 + (int) ($w * 0.35), 760, $c);
                imagepng($img, $tmp);
                $file = new UploadedFile($tmp, "foto-$angle.png", 'image/png', null, true);
                Files::store($file, $p, 'foto', ['angle' => $angle, 'taken_on' => Clock::today()->subDays($ago)->toDateString()]);
                @unlink($tmp);
            }
        }
    }

    private function at(CarbonImmutable $day, int $hour): CarbonImmutable
    {
        return CarbonImmutable::parse($day->toDateString().' '.$hour.':00', config('bioma.timezone'))->utc();
    }
}
