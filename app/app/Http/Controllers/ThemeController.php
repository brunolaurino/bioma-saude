<?php

namespace App\Http\Controllers;

use App\Support\Theme;
use Illuminate\Http\Request;

/** Guarda a escolha de tema. Com JavaScript a troca é instantânea e isto só salva; sem ele, volta para a tela. */
class ThemeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['tema' => ['required', 'in:auto,claro,escuro']]);
        $response = $request->expectsJson() ? response()->noContent() : back();

        return $data['tema'] === 'auto'
            ? $response->withoutCookie(Theme::COOKIE)
            : $response->cookie(Theme::COOKIE, $data['tema'], 60 * 24 * 365);
    }
}
