@extends('layouts.entry', ['title' => 'Segundo fator'])
@section('content')
<div class="stack-4">
  <span class="brand-mark brand-mark--lg"><x-ic n="shield-halved" /></span>
  <h1 class="t-title">Confirme que é você</h1>
  <p class="muted">{{ $recovery ? 'Digite um dos códigos de recuperação que você guardou.' : 'Digite o código de 6 dígitos do seu app autenticador.' }}</p>
  <form method="POST" action="{{ route('2fa.challenge') }}" class="stack-4" novalidate>
    @csrf
    @if ($recovery)
      <x-field name="code" label="Código de recuperação" icon="life-ring" placeholder="ABCDE-12345" autocomplete="off" spellcheck="false" required />
    @else
      <x-field name="code" label="Código do app" icon="shield-halved" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required />
    @endif
    <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="right-to-bracket" /> Entrar</button>
  </form>
  <a class="bc-link center block" href="{{ route('2fa.challenge', $recovery ? [] : ['recuperacao' => 1]) }}">{{ $recovery ? 'Usar o app autenticador' : 'Perdi o celular: usar código de recuperação' }}</a>
  <form method="POST" action="{{ route('logout') }}">@csrf<button class="bc-btn bc-btn--ghost bc-btn--block"><x-ic n="right-from-bracket" /> Sair</button></form>
</div>
@endsection
