@php
  use App\Domain\{Catalog, Indicators}; use App\Support\{Clock, Fmt};
  $nl = $next ? Clock::local($next->starts_at) : null;
@endphp
@extends('layouts.pro', ['title' => 'Preparação da consulta', 'sub' => $patient->name.($nl ? ' · '.Fmt::dm($nl).' às '.Fmt::time($nl).' · '.$next->type : ''), 'back' => route('pro.patient', $patient)])
@section('actions')
<a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ route('pro.patient', $patient) }}"><x-ic n="folder-open" /> <span class="sm-hide">Prontuário</span></a>
@endsection
@section('content')
<p class="privacy-line"><x-ic n="clock-rotate-left" /> Tudo o que {{ $patient->firstName() }} registrou desde {{ $since ? 'a última consulta, em '.Fmt::dmy($since) : 'o início do acompanhamento' }}.</p>
<div class="lg-grid-2 stack-6-mobile">
  <x-section title="Pontos para conversar">
    @if (empty($topics))
      <p class="muted t-body-sm">Nada que chame atenção no período.</p>
    @else
      <div class="bc-card divide">
        @foreach ($topics as $i => $t)
          <label class="row-item check prep-topic"><input type="checkbox"> <span>{{ $t }}</span></label>
        @endforeach
      </div>
      <p class="t-caption muted">As marcações servem só para você durante a consulta e não são salvas.</p>
    @endif
  </x-section>

  @if ($cur)
  <x-section title="Bioimpedância desde a última consulta">
    <div class="bc-card divide">
      @foreach (Indicators::ALL as $k => $i)
        <div class="row-item t-body-sm">
          <span class="ic-col"><x-ic :n="$i['icon']" /></span><span class="grow">{{ $i['s'] }}</span>
          @if ($base)<span class="t-caption muted tnum">{{ Fmt::num($base[$k], $i['dec']) }} →</span>@endif
          <b class="tnum w-14 right">{{ Fmt::num($cur[$k], $i['dec']) }}</b>
          <span class="w-20 right">@if ($base)<x-delta :d="$cur[$k] - $base[$k]" :ind="$i" />@endif</span>
        </div>
      @endforeach
    </div>
  </x-section>
  @endif

  <x-section :title="'Sintomas ('.$symptoms->count().')'">
    @forelse ($symptoms->sortByDesc(fn ($s) => $s->day->timestamp) as $s)
      @php($tone = Catalog::INT_TONE[$s->intensity])
      <div class="bc-card p-3 flex">
        <span class="sev-dot dot-{{ $tone }}" aria-hidden="true"></span>
        <div class="grow"><b>{{ $s->symptom }}</b> <x-pill :tone="$tone" icon="circle">{{ $s->intensity }}</x-pill><div class="t-caption muted">{{ Fmt::wd($s->day) }}, {{ Fmt::dm($s->day) }}{{ $s->note ? ' · “'.$s->note.'”' : '' }}</div></div>
      </div>
    @empty
      <p class="muted t-body-sm">Nenhum sintoma no período.</p>
    @endforelse
  </x-section>

  <x-section :title="'Desabafos ('.$journal->count().')'">
    @forelse ($journal as $j)
      @php($m = Catalog::mood((int) $j->mood))
      <div class="bc-card p-3 stack-1">
        <div class="t-caption muted flex gap-2"><span class="t-heading c-{{ Catalog::moodTone((int) $j->mood) }}"><x-ic :n="$m['icon']" :r="true" /></span>{{ Fmt::dm($j->day) }} · {{ $m['l'] }}</div>
        <p class="t-body-sm">{{ $j->text }}</p>
      </div>
    @empty
      <p class="muted t-body-sm">Nenhum desabafo compartilhado no período.</p>
    @endforelse
  </x-section>
</div>
<a class="bc-btn bc-btn--primary" href="{{ route('pro.patient', [$patient, 'aba' => 'notas']) }}"><x-ic n="play" /> Iniciar consulta e anotar</a>
@endsection
