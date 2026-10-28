@extends('layouts.patient', ['title' => 'Meu cuidado'])
@section('tab', 'care')
@section('header')<x-header title="Meu cuidado" sub="Acompanhamento multidisciplinar" />@endsection
@section('content')
<div class="screen">
  <div class="stack-3">
    @foreach ($cards as $c)
      <a class="bc-card bc-card--action p-4 flex-start" href="{{ route($c['route']) }}">
        <span class="circle-ic circle-ic--lg"><x-ic :n="$c['icon']" /></span>
        <div class="grow">
          <div class="bold">{{ $c['t'] }}</div><div class="t-caption muted mb-1">{{ $c['who'] }}</div>
          <ul class="plain t-caption stack-1">@foreach ($c['lines'] as $l)<li class="bullet">{{ $l }}</li>@endforeach</ul>
        </div>
        <x-ic n="chevron-right" class="muted" />
      </a>
    @endforeach
  </div>
  <div class="grid-2">
    @if ($m['atividade'])<a class="bc-btn bc-btn--secondary" href="{{ route('workouts.create') }}"><x-ic n="dumbbell" /> Registrar treino</a>@endif
    @if ($m['saude_mental'])<a class="bc-btn bc-btn--secondary" href="{{ route('journal.create') }}"><x-ic n="pen-to-square" /> Desabafar</a>@endif
  </div>
</div>
@endsection
