@extends('layouts.simple', ['title' => 'Fazer um pedido', 'back' => route('privacy')])
@section('content')
<p class="muted">O responsável pelos seus dados responde em até {{ $days }} dias. Você acompanha a resposta em Privacidade e dados.</p>
<form method="POST" action="{{ route('privacy.request') }}" class="stack-4" novalidate>
  @csrf
  <fieldset class="stack-2">
    <legend class="bc-section__title">O que você quer?</legend>
    @foreach ($types as $k => $l)
      <label class="check bc-card p-3"><input type="radio" name="type" value="{{ $k }}" @checked(old('type') === $k)><span>{{ $l }}</span></label>
    @endforeach
    @error('type')<p class="bc-field__error"><x-ic n="circle-exclamation" /> {{ $message }}</p>@enderror
  </fieldset>
  <x-textarea name="details" label="Detalhes (opcional)" :rows="4" placeholder="Ex.: meu telefone está errado; quero saber quem acessou meus exames." maxlength="2000" />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="paper-plane" /> Enviar pedido</button>
</form>
@endsection
