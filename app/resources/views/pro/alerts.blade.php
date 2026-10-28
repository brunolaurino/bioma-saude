@extends('layouts.pro', ['title' => 'Alertas', 'sub' => $openCount.' '.\App\Support\Fmt::plural($openCount, 'aberto', 'abertos')])
@php use App\Http\Controllers\Pro\AlertsController; @endphp
@section('content')
<div class="lg-grid-side stack-6-mobile">
  <div class="stack-4">
    <div class="bc-seg" role="group" aria-label="Situação">
      @foreach (AlertsController::VIEWS as $k => $l)
        <a class="bc-seg-link" href="{{ route('pro.alerts', ['ver' => $k, 'gravidade' => $sev]) }}" @if($view === $k) aria-current="true" @endif>{{ $l }}</a>
      @endforeach
    </div>
    <nav class="chips-scroll" aria-label="Gravidade">
      @foreach (AlertsController::SEVERITIES as $k => $l)
        <a class="bc-chip" href="{{ route('pro.alerts', ['ver' => $view, 'gravidade' => $k]) }}" @if($sev === $k) aria-current="true" @endif>{{ $l }}</a>
      @endforeach
    </nav>
    <div class="stack-2">
      @forelse ($list as $a)
        <x-alert-row :alert="$a" :name="$names[$a->patient_id] ?? 'Paciente'" />
      @empty
        <x-empty icon="circle-check" title="Nada por aqui" text="Nenhum alerta com esses filtros nos últimos 90 dias." />
      @endforelse
    </div>
  </div>
  <div class="stack-6">
    <x-section title="Sintomas mais relatados (30 dias)">
      <div class="bc-card p-4">@if ($freq->isEmpty())<p class="muted t-body-sm">Nenhum sintoma registrado.</p>@else<x-hbars :rows="$freq->take(8)" />@endif</div>
    </x-section>
    <x-section title="Como os alertas funcionam">
      <div class="bc-card p-3 stack-2 t-caption muted">
        <div class="flex gap-2"><x-pill tone="bad" icon="circle">Forte</x-pill> gera alerta vermelho na hora</div>
        <div class="flex gap-2"><x-pill tone="warn" icon="circle">Moderada</x-pill> gera alerta amarelo</div>
        <div class="flex gap-2"><x-pill tone="ok" icon="circle">Leve</x-pill> fica só no relatório</div>
        <p>Os alertas só avisam a equipe. Nenhuma decisão sobre o paciente é tomada de forma automática.</p>
        @if ($proNav['master'])<a class="bc-link" href="{{ route('pro.settings') }}#regras">Ajustar regras</a>@endif
      </div>
    </x-section>
  </div>
</div>
@endsection
