<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\ProfileController;
use Illuminate\Http\Request;

/** Conta do próprio profissional: aparelhos conectados, senha e 2FA. */
class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();

        return view('pro.account', [
            'user' => $user, 'pro' => $user->professional,
            'sessions' => ProfileController::deviceList($request),
            'recoveryLeft' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    public function endSessions(Request $request)
    {
        ProfileController::endOtherSessions($request);

        return back()->with('status', 'Você saiu dos outros aparelhos.');
    }

    public function password(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('shared.password', ['action' => route('pro.account.password'), 'back' => route('pro.account')]);
        }
        ProfileController::changePassword($request);

        return redirect()->route('pro.account')->with('status', 'Senha alterada. Os outros aparelhos foram desconectados.');
    }
}
