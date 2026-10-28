<?php

namespace App\Http\Controllers;

use App\Security\Modules;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function root(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route($request->cookie('bioma_seen') ? 'login' : 'onboarding');
        }

        return redirect()->route($user->isPatient() ? 'home' : 'pro.dashboard');
    }

    public function onboarding(Request $request)
    {
        $step = max(1, min(3, (int) $request->query('passo', 1)));

        return response()->view('auth.onboarding', ['step' => $step, 'lead' => Settings::leadName()])
            ->cookie('bioma_seen', '1', 60 * 24 * 365, null, null, null, true, false, 'lax');
    }

    public function notice()
    {
        $notice = DB::table('privacy_notices')->orderByDesc('version')->first();

        return view('auth.notice', ['notice' => $notice]);
    }

    public function about()
    {
        return view('auth.about', ['modules' => Modules::map()]);
    }
}
