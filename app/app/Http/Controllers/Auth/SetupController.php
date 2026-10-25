<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Invites;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Professional;
use App\Models\User;
use App\Security\Audit;
use App\Security\BlindIndex;
use App\Security\Passwords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Criação de conta profissional por link de uso único:
 * - master: link gerado no servidor por `php artisan bioma:master` (só existe um master);
 * - equipe: link do convite que o master envia em Configurações > Equipe.
 * Depois da senha, o 2FA é obrigatório antes de abrir o painel.
 */
class SetupController extends Controller
{
    public function show(string $token)
    {
        $ctx = $this->resolve($token);
        abort_unless($ctx, 404, 'Este link expirou ou já foi usado.');

        return view('auth.setup', ['token' => $token] + $ctx);
    }

    public function store(Request $request, string $token)
    {
        $ctx = $this->resolve($token);
        abort_unless($ctx, 404, 'Este link expirou ou já foi usado.');
        $master = $ctx['kind'] === 'master';

        $data = $request->validate([
            'name' => [$master ? 'required' : 'nullable', 'string', 'max:160'],
            'email' => [$master ? 'required' : 'nullable', 'email:rfc', 'max:190'],
            'specialty' => ['required', Rule::in(array_keys(Professional::SPECIALTIES))],
            'council_number' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:128', 'confirmed'],
        ], [
            'name.required' => 'Digite seu nome.',
            'email.*' => 'Digite um e-mail válido.',
            'specialty.*' => 'Escolha a especialidade.',
            'password.required' => 'Crie uma senha.',
            'password.confirmed' => 'As duas senhas não são iguais.',
        ]);

        if ($master && User::findByEmail($data['email'])) {
            return back()->withErrors(['email' => 'Já existe uma conta com este e-mail.'])->withInput();
        }

        $name = $master ? $data['name'] : $ctx['name'];
        $email = $master ? $data['email'] : $ctx['email'];
        if ($problem = Passwords::problem($data['password'], [$email, $name])) {
            return back()->withErrors(['password' => $problem])->withInput();
        }

        $user = DB::transaction(function () use ($ctx, $master, $data, $name, $token) {
            if ($master) {
                $user = new User(['type' => 'professional', 'status' => 'active']);
                $user->setEmailAddress($data['email']);
                $user->password = $data['password'];
                $user->save();
                DB::table('setup_tokens')->where('token_hash', BlindIndex::token($token))->update(['used_at' => now()]);
            } else {
                $user = $ctx['invite']->user;
                $user->forceFill(['password' => $data['password'], 'status' => 'active'])->save();
                $ctx['invite']->forceFill(['used_at' => now()])->save();
            }

            Professional::updateOrCreate(['user_id' => $user->id], [
                'name' => $name,
                'role' => $master ? 'master' : 'member',
                'specialty' => $data['specialty'],
                'council' => Professional::SPECIALTIES[$data['specialty']]['council'],
                'council_number' => $data['council_number'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.started_at', time());
        $request->session()->put('auth.last_seen', time());
        Audit::log('auth.account_created', 'user:'.$user->id, null, ['role' => $master ? 'master' : 'member']);

        return redirect()->route('2fa.setup')->with('status', 'Conta criada. Agora proteja o acesso com o segundo fator.');
    }

    /** @return ?array{kind:string, name:?string, email:?string, invite:?Invitation} */
    private function resolve(string $token): ?array
    {
        if (strlen($token) > 100) {
            return null;
        }

        $row = DB::table('setup_tokens')->where('token_hash', BlindIndex::token($token))->whereNull('used_at')->where('expires_at', '>', now())->first();
        if ($row && $row->purpose === 'master' && ! Professional::where('role', 'master')->exists()) {
            return ['kind' => 'master', 'name' => null, 'email' => null, 'invite' => null];
        }

        $invite = Invites::find($token, 'professional');
        if ($invite && $invite->user && $invite->user->professional) {
            return ['kind' => 'member', 'name' => $invite->user->professional->name, 'email' => $invite->user->email, 'invite' => $invite];
        }

        return null;
    }
}
