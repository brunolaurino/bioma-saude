<?php

namespace App\Http\Controllers;

use App\Domain\Push;
use App\Http\Controllers\Patient\ProfileController;
use App\Security\Audit;
use Illuminate\Http\Request;

/** Liga e desliga as notificações push deste aparelho (pacientes e equipe). */
class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        abort_unless(Push::enabled(), 404);
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url:https'],
            'p256dh' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+=*$/'],
            'auth' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_\-]+=*$/'],
        ], ['*' => 'Não foi possível ativar as notificações neste aparelho.']);

        $sub = Push::subscribe($request->user(), $data['endpoint'], $data['p256dh'], $data['auth'], ProfileController::agent((string) $request->userAgent()));
        Audit::log('push.subscribe', 'push:'.$sub->id);

        return back()->with('status', 'Notificações ativadas neste aparelho.');
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => ['nullable', 'string', 'max:1000']]);
        if (Push::unsubscribe($request->user(), $data['endpoint'] ?? null)) {
            Audit::log('push.unsubscribe', 'user:'.$request->user()->id);
        }

        return back()->with('status', 'Notificações desativadas neste aparelho.');
    }
}
