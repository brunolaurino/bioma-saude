<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Security\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ReauthController extends Controller
{
    public function show()
    {
        return view('auth.reauth', ['back' => url()->previous()]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['password' => ['required', 'string', 'max:200']], ['password.required' => 'Digite sua senha.']);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            Audit::log('auth.reauth.fail', 'user:'.$request->user()->id);

            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        $request->session()->put('auth.reauth_at', time());

        return redirect()->intended($request->user()->isPatient() ? route('privacy') : route('pro.account'));
    }
}
