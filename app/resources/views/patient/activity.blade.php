@extends('layouts.base', ['title' => 'Atividade física'])
@php use App\Support\Fmt; @endphp
@section('body')
<div class="app-patient">
  <x-header title="Atividade física" :sub="$who" :back="route('care')">
    <x-slot:right><a class="bc-iconbtn" href="{{ route('workouts.create') }}" aria-label="Registrar treino"><x-ic n="plus" /></a></x-slot:right>
  </x-header>
  <main id="conteudo" class="no-tabs"><div class="screen">
    <div class="grid-3 center">
      @foreach ([['Força', $last7->where('category', 'Força')->count(), 'dumbbell'], ['Aeróbico', $last7->where('category', 'Aeróbico')->count(), 'heart-pulse'], ['Minutos', $last7->sum('minutes'), 'stopwatch']] as [$l, $v, $ic])
        <div class="bc-card p-2"><div class="muted"><x-ic :n="$ic" /></div><div class="t-num">{{ $v }}</div><div class="t-overline muted">{{ $l }} · 7 dias</div></div>
      @endforeach
    </div>
    <div class="grid-7 center" role="img" aria-label="{{ $week->filter(fn ($d) => $d['w'])->count() }} dias com treino nos últimos 7">
      @foreach ($week as $d)
        <div class="stack-1"><span class="t-caption muted">{{ Fmt::wd($d['d']) }}</span><span @class(['week-dot', 'on' => $d['w']])>@if ($d['w'])<x-ic :n="$d['w']->category === 'Força' ? 'dumbbell' : 'heart-pulse'" /> @else{{ $d['d']->day }}@endif</span></div>
      @endforeach
    </div>
    <x-section title="Hoje">
      @if ($today->isNotEmpty())
        <div class="bc-card divide">@foreach ($today as $w)<x-workout-row :w="$w" /> @endforeach</div>
      @else
        <x-empty icon="person-running" title="Nenhum treino hoje" text="Registre força ou aeróbico assim que terminar."><a class="bc-btn bc-btn--primary mt-2" href="{{ route('workouts.create') }}"><x-ic n="plus" /> Registrar treino</a></x-empty>
      @endif
    </x-section>
    @if ($history->isNotEmpty())
    <x-section title="Histórico">
      <div class="bc-card divide">@foreach ($history as $w)<x-workout-row :w="$w" :show-date="true" /> @endforeach</div>
    </x-section>
    @endif
  </div></main>
</div>
@endsection
