@extends('layouts.entry', ['title' => 'Criar conta'])
@section('content')
<div class="center stack-3">
  <span class="brand-mark brand-mark--lg"><x-ic n="user-doctor" /></span>
  <h1 class="t-title">{{ $kind === 'master' ? 'Configurar o Bioma CD' : 'Entrar para a equipe' }}</h1>
  <p class="muted">{{ $kind === 'master' ? 'Você será o responsável (master) por este ambiente: liga e desliga funções, convida a equipe e responde pelos dados.' : 'Olá, '.$name.'. Crie sua senha para acessar o painel.' }}</p>
</div>
<form method="POST" action="{{ route('setup', $token) }}" class="stack-4 mt-6" novalidate>
  @csrf
  @if ($kind === 'master')
    <x-field name="name" label="Nome como aparece para os pacientes" icon="user" placeholder="Dra. Marina Albuquerque" autocomplete="name" required />
    <x-field name="email" label="E-mail" icon="envelope" type="email" autocomplete="username" required />
  @else
    <div class="bc-card p-3 flex"><x-avatar :name="$name" /><div><div class="bold">{{ $name }}</div><div class="t-caption muted">{{ $email }}</div></div></div>
  @endif
  <div class="bc-field {{ $errors->has('specialty') ? 'bc-field--error' : '' }}">
    <label class="bc-field__label" for="f-specialty">Especialidade</label>
    <select id="f-specialty" name="specialty" class="select" required>
      <option value="">Escolha</option>
      @foreach (\App\Models\Professional::SPECIALTIES as $s => $meta)
        <option value="{{ $s }}" @selected(old('specialty') === $s)>{{ $s }} ({{ $meta['council'] }})</option>
      @endforeach
    </select>
    @error('specialty')<span class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</span>@enderror
  </div>
  <x-field name="council_number" label="Número no conselho" icon="id-card" placeholder="123456/SP" />
  <x-field name="password" label="Senha" icon="key" type="password" autocomplete="new-password" hint="Mínimo de 10 caracteres." required />
  <x-field name="password_confirmation" label="Repita a senha" icon="key" type="password" autocomplete="new-password" required />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Criar conta</button>
  <p class="t-caption muted center">No próximo passo você liga o segundo fator com um app autenticador (Google Authenticator, Microsoft Authenticator ou 1Password).</p>
</form>
@endsection
