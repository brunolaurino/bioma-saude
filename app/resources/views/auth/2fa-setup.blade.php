@extends('layouts.entry', ['title' => 'Segundo fator'])
@section('content')
<div class="stack-4">
  <h1 class="t-title">Proteja sua conta</h1>
  <p class="muted">Profissionais acessam dados de saúde, então o segundo fator é obrigatório. Abra um app autenticador e leia o código abaixo.</p>
  <div class="center qr">{!! $qr !!}</div>
  <p class="t-caption muted center">Não consegue ler? Digite esta chave no app:<br><span class="kbd">{{ $secret }}</span></p>
  <form method="POST" action="{{ route('2fa.setup') }}" class="stack-4" novalidate>
    @csrf
    <x-field name="code" label="Código de 6 dígitos do app" icon="shield-halved" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required />
    <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Ativar</button>
  </form>
  <form method="POST" action="{{ route('logout') }}">@csrf<button class="bc-btn bc-btn--ghost bc-btn--block"><x-ic n="right-from-bracket" /> Sair</button></form>
</div>
@endsection
