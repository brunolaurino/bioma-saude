<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tema claro, escuro ou automático, guardado num cookie e aplicado no <html>. */
class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_escolha_de_tema_vai_para_o_cookie_e_para_a_pagina(): void
    {
        $this->get('/entrar')->assertOk()->assertDontSee('data-theme=', false);
        $this->post('/tema', ['tema' => 'escuro'])->assertRedirect()->assertCookie('tema', 'escuro');
        $this->withCookie('tema', 'escuro')->get('/entrar')->assertOk()->assertSee('data-theme="dark"', false);
        $this->withCookie('tema', 'claro')->get('/entrar')->assertSee('data-theme="light"', false);
    }

    public function test_automatico_apaga_o_cookie_e_valor_invalido_e_recusado(): void
    {
        $this->postJson('/tema', ['tema' => 'auto'])->assertNoContent()->assertCookieExpired('tema');
        $this->post('/tema', ['tema' => 'roxo'])->assertSessionHasErrors('tema');
    }
}
