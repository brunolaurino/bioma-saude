@extends('layouts.entry', ['title' => 'Entrar'])
@section('content')
<div class="center stack-3">
  <span class="brand-mark brand-mark--lg"><x-ic n="leaf" /></span>
  <h1 class="t-title">Bioma CD<br><span class="normal muted t-heading">Plataforma de Evolução Saudável</span></h1>
  <p class="t-caption muted">Acompanhamento de {{ $lead }}</p>
</div>
<form method="POST" action="{{ route('login') }}" class="stack-4 mt-6" novalidate>
  @csrf
  <div class="bc-seg" role="group" aria-label="Tipo de conta">
    <a class="bc-seg-link" href="{{ route('login') }}" @if($who === 'pac') aria-current="true" @endif><x-ic n="user" /> Sou paciente</a>
    <a class="bc-seg-link" href="{{ route('login', ['perfil' => 'profissional']) }}" @if($who === 'pro') aria-current="true" @endif><x-ic n="user-doctor" /> Sou profissional</a>
  </div>
  <x-field name="email" label="E-mail" icon="envelope" type="email" autocomplete="username" inputmode="email" :placeholder="$who === 'pac' ? 'voce@email.com' : 'nome@clinica.com.br'" required />
  <x-field name="password" label="Senha" icon="key" type="password" autocomplete="current-password" placeholder="Sua senha" required>
    <button type="button" class="bc-iconbtn" data-reveal aria-label="Mostrar senha"><x-ic n="eye" /></button>
  </x-field>
  <div class="right"><a class="bc-link" href="{{ route('password.request') }}">Esqueci minha senha</a></div>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="right-to-bracket" /> Entrar</button>
  @if ($who === 'pac')
    <a class="bc-btn bc-btn--secondary bc-btn--block" href="{{ route('first') }}"><x-ic n="ticket" /> Primeiro acesso com convite</a>
  @else
    <p class="t-caption muted center">Profissionais entram pelo link de convite enviado pelo responsável do consultório. O segundo fator (app autenticador) é obrigatório.</p>
  @endif
</form>
<div class="entry__foot privacy-line justify-center"><x-ic n="lock" /> Dados de saúde protegidos conforme a LGPD · <a class="bc-link" href="{{ route('privacy.notice') }}">Aviso de privacidade</a></div>
@endsection
