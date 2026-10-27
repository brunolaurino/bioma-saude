@extends('layouts.entry', ['title' => 'Bem-vindo'])
@php($slides = [
  ['chart-line', 'Veja sua evolução', 'Registre sua bioimpedância e acompanhe peso, gordura e músculo em gráficos simples.'],
  ['list-check', 'Metas simples, todo dia', 'Água, proteína, frutas e verduras: marque em segundos e mantenha a sequência.'],
  ['user-doctor', 'Sua equipe por perto', 'Nutrição, treino e terapia no mesmo lugar. '.$lead.' acompanha tudo antes de cada consulta.'],
])
@php([$icon, $heading, $text] = $slides[$step - 1])
@section('content')
<div class="between">
  <span class="flex gap-2 bold"><x-brand :size="32" />Saúde App</span>
  <a class="bc-link" href="{{ route('login') }}">Pular</a>
</div>
<div class="grow flex-col-center center stack-5">
  <span class="brand-mark brand-mark--xl"><x-ic :n="$icon" /></span>
  <div class="t-overline muted">Como funciona · {{ $step }} de 3</div>
  <h1 class="t-title">{{ $heading }}</h1>
  <p class="muted max-text">{{ $text }}</p>
</div>
<div class="stack-5">
  <div class="dots" aria-hidden="true">@for ($i = 1; $i <= 3; $i++)<span @class(['on' => $i === $step])></span>@endfor</div>
  <div class="flex gap-2">
    @if ($step > 1)
      <a class="bc-btn bc-btn--secondary" href="{{ route('onboarding', ['passo' => $step - 1]) }}" aria-label="Anterior"><x-ic n="arrow-left" /></a>
    @endif
    @if ($step < 3)
      <a class="bc-btn bc-btn--primary grow" href="{{ route('onboarding', ['passo' => $step + 1]) }}">Próximo <x-ic n="arrow-right" /></a>
    @else
      <a class="bc-btn bc-btn--primary grow" href="{{ route('login') }}">Começar <x-ic n="arrow-right" /></a>
    @endif
  </div>
</div>
@endsection
