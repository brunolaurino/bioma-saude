<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\Audit;
use App\Security\BlindIndex;
use App\Security\Passwords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Link de uso único, válido por 30 minutos. A resposta é igual para e-mail cadastrado ou não. */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']], ['email.*' => 'Digite um e-mail válido.']);
        $user = User::findByEmail($data['email']);

        if ($user && $user->status === 'active' && $user->password) {
            $token = Str::random(48);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email_hash' => $user->email_hash],
                ['token_hash' => BlindIndex::token($token), 'created_at' => now()],
            );
            $link = route('password.reset', $token);
            try {
                Mail::raw("Para criar uma nova senha no Bioma CD, abra este link em até 30 minutos:\n\n$link\n\nSe você não pediu, ignore este e-mail. Sua senha continua a mesma.", function ($m) use ($user) {
                    $m->to($user->email)->subject('Bioma CD: criar nova senha');
                });
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar e-mail de recuperação de senha.');
            }
            Audit::record('anônimo', 'auth.password.reset_requested', 'user:'.$user->id, null, [], $user->id);
        }

        return back()->with('sent', mb_strtolower(trim($data['email'])));
    }

    public function edit(string $token)
    {
        return view('auth.reset', ['token' => $token]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:128'],
        ], ['email.*' => 'Digite o e-mail da sua conta.', 'password.required' => 'Digite a nova senha.']);

        $user = User::findByEmail($data['email']);
        $row = $user ? DB::table('password_reset_tokens')->where('email_hash', $user->email_hash)->first() : null;
        $valid = $row && hash_equals($row->token_hash, BlindIndex::token($data['token'])) && now()->subMinutes(30)->lt($row->created_at);

        if (! $valid) {
            return back()->withErrors(['email' => 'Este link expirou ou não vale para este e-mail. Peça um novo.'])->withInput($request->only('email'));
        }

        if ($problem = Passwords::problem($data['password'], [$data['email'], $user->displayName()])) {
            return back()->withErrors(['password' => $problem])->withInput($request->only('email'));
        }

        $user->forceFill(['password' => $data['password'], 'failed_logins' => 0, 'locked_until' => null])->save();
        DB::table('password_reset_tokens')->where('email_hash', $user->email_hash)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Audit::record('anônimo', 'auth.password.reset', 'user:'.$user->id, $user->patient?->id, [], $user->id);

        return redirect()->route('login')->with('status', 'Senha criada. Entre com a nova senha.');
    }
}
