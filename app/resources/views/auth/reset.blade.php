@extends('layouts.simple', ['title' => 'Nova senha', 'back' => route('login')])
@section('content')
<p class="muted">Confirme seu e-mail e crie uma senha nova. Ao salvar, você sai de todos os aparelhos.</p>
<form method="POST" action="{{ route('password.update') }}" class="stack-4" novalidate>
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <x-field name="email" label="E-mail" icon="envelope" type="email" autocomplete="username" required />
  <x-field name="password" label="Nova senha" icon="key" type="password" autocomplete="new-password" hint="Mínimo de 10 caracteres. Uma frase curta é fácil de lembrar e difícil de adivinhar." required />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Salvar nova senha</button>
</form>
@endsection
