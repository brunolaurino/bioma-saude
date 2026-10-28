@extends('layouts.simple', ['title' => 'Primeiro acesso', 'back' => route('first')])
@section('content')
<p class="t-caption muted">Etapa 2 de 3 · Crie sua senha</p>
@include('auth._steps', ['step' => 2])
<div class="bc-card flex p-3"><x-avatar :name="$patient->name" /><div><div class="bold">{{ $patient->name }}</div><div class="t-caption muted">{{ $email }}</div></div></div>
<form method="POST" action="{{ route('first.password') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="password" label="Nova senha" icon="key" type="password" autocomplete="new-password" hint="Mínimo de 10 caracteres. Uma frase curta é fácil de lembrar e difícil de adivinhar." required />
  <x-field name="password_confirmation" label="Repita a senha" icon="key" type="password" autocomplete="new-password" required />
  <div class="bc-card p-3 stack-2">
    <p class="t-body-sm">Seus dados de saúde são tratados por {{ $lead }} e equipe para acompanhar seu tratamento (LGPD, art. 11, II, f). Eles ficam cifrados e só a equipe que cuida de você pode ver.</p>
    <a class="bc-link t-body-sm" href="{{ route('privacy.notice') }}" target="_blank" rel="noopener"><x-ic n="file-shield" /> Ler o aviso de privacidade completo{{ $noticeVersion ? ' (versão '.$noticeVersion.')' : '' }}</a>
  </div>
  <label class="check">
    <input type="checkbox" name="notice" value="1" @checked(old('notice'))>
    <span>Li o aviso de privacidade e entendi como meus dados serão usados.</span>
  </label>
  @error('notice')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  <button class="bc-btn bc-btn--primary bc-btn--block">Continuar <x-ic n="arrow-right" /></button>
</form>
@endsection
