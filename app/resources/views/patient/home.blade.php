@extends('layouts.patient', ['title' => 'Início'])
@php use App\Domain\Indicators; use App\Support\{Clock, Fmt}; @endphp
@section('tab', 'home')
@section('header')
<x-header :title="'Olá, '.$patient->firstName()" :sub="Fmt::wdl(Clock::today()).', '.Fmt::dmy(Clock::today())">
  <x-slot:left><a href="{{ route('profile') }}" aria-label="Meu perfil"><x-avatar :name="$patient->name" /></a></x-slot:left>
  <x-slot:right>
    <a class="bc-iconbtn" href="{{ route('notifications') }}" aria-label="Notificações">
      <x-ic n="bell" />@if ($nav['unread'] > 0)<span class="bc-badge">{{ $nav['unread'] }}</span>@endif
    </a>
  </x-slot:right>
</x-header>
@endsection
@section('content')
<div class="screen">
  @if ($showInstall)
  <div class="notice notice--dashed">
    <span class="ic"><x-ic n="mobile-screen-button" /></span>
    <div class="grow t-caption"><b>Instale o Saúde App</b>Abra direto da tela inicial, como um aplicativo.</div>
    <button type="button" class="bc-btn bc-btn--secondary bc-btn--sm" data-open="sheet-install">Instalar</button>
    <form method="POST" action="{{ route('install.dismiss') }}">@csrf<button class="bc-iconbtn" aria-label="Dispensar"><x-ic n="xmark" /></button></form>
  </div>
  @endif

  @if ($bio->isNotEmpty())
    @php
      $first = $bio->first(); $last = $bio->last(); $weights = $bio->pluck('w')->all();
      $wt = Indicators::trend($weights, 'down'); $goalW = $goals['weight'];
      $prog = $goalW !== null && $first['w'] != $goalW ? max(0, min(100, ($first['w'] - $last['w']) / ($first['w'] - $goalW) * 100)) : null;
      $since = Fmt::daysAgo($last['d']);
    @endphp
    <a class="bc-card bc-card--action hero-card on-dark p-4 stack-3" href="{{ route('evolution') }}">
      <div class="between flex-start">
        <div>
          <div class="t-caption bold muted"><x-ic n="weight-scale" /> Peso atual</div>
          <div class="t-num-xl">{{ Fmt::num($last['w']) }} <span class="t-heading muted">kg</span></div>
          @if ($bio->count() > 1)<div class="t-caption muted"><x-delta :d="$last['w'] - $first['w']" :ind="Indicators::get('w')" /> desde {{ Fmt::short($first['d']) }}</div>@endif
        </div>
        <div class="stack-2 right"><x-pill :tone="$wt['tone']" /><x-spark :values="$weights" :tone="$wt['tone']" :w="96" :h="34" /></div>
      </div>
      @if ($prog !== null)
      <div class="stack-1">
        <div class="between t-caption muted tnum"><span>Início {{ Fmt::num($first['w']) }} kg</span><span>{{ round($prog) }}% do caminho</span><span>Meta {{ Fmt::num($goalW) }} kg</span></div>
        <x-bar :value="$prog" tone="ok" />
      </div>
      @endif
    </a>
    @if ($since > $bioInterval)
    <div class="notice notice--warn">
      <span class="ic"><x-ic n="calendar-xmark" /></span>
      <div class="grow t-caption"><b>Hora da bioimpedância</b>Última medição há {{ $since }} dias, em {{ Fmt::short($last['d']) }}.</div>
      <a class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('bio.create') }}">Registrar</a>
    </div>
    @endif
  @elseif ($m['bioimpedancia'])
    <a class="bc-card bc-card--dashed bc-card--action p-4 flex" href="{{ route('bio.create') }}">
      <span class="circle-ic circle-ic--lg"><x-ic n="weight-scale" /></span>
      <div class="grow"><b>Registre sua primeira bioimpedância</b><div class="t-caption muted">Digite os 5 valores do laudo para ver sua evolução.</div></div>
      <x-ic n="chevron-right" class="muted" />
    </a>
  @endif

  @include('patient.partials.quick-tiles')

  @if ($active)
  <x-section title="Metas de hoje">
    <x-slot:action><a class="bc-link" href="{{ route('habits') }}">Ver tudo <x-ic n="chevron-right" /></a></x-slot:action>
    <div class="bc-card p-3">
      <div class="flex pb-3 border-b">
        <x-ring :value="$done" :total="count($active)" />
        <div class="grow"><b>{{ $done >= count($active) ? 'Dia completo!' : 'Faltam '.(count($active) - $done).' '.Fmt::plural(count($active) - $done, 'meta', 'metas') }}</b>
          @if (in_array('water', $active))<div class="t-caption muted">Sequência de água: {{ $streak }} {{ Fmt::plural($streak, 'dia', 'dias') }}</div>@endif
        </div>
      </div>
      <div class="divide">
        @if (in_array('water', $active))
        <div class="row-item px-0">
          <span class="ic-col t-heading"><x-ic n="glass-water" /></span>
          <div class="grow"><div class="bold">Água</div><div class="t-caption muted tnum">{{ Fmt::int($day['water']) }} de {{ Fmt::int($goals['water']) }} ml</div><x-bar :value="$day['water'] / max(1, $goals['water']) * 100" :tone="$day['water'] >= $goals['water'] ? 'ok' : null" :sm="true" /></div>
          <form method="POST" action="{{ route('habits.water') }}">@csrf<input type="hidden" name="add" value="250"><button class="bc-btn bc-btn--secondary bc-btn--sm">+250 ml</button></form>
        </div>
        @endif
        @if (in_array('protein', $active))
        <div class="row-item px-0">
          <span class="ic-col t-heading"><x-ic n="drumstick-bite" /></span>
          <div class="grow"><div class="bold">Proteína</div><div class="t-caption muted">Meta: {{ $goals['protein'] }} g</div></div>
          <form method="POST" action="{{ route('habits.protein') }}">@csrf<input type="hidden" name="value" value="{{ $day['protein'] === true ? 'clear' : '1' }}">
            <button class="bc-btn bc-btn--sm {{ $day['protein'] === true ? 'bc-btn--ok' : 'bc-btn--secondary' }}" aria-pressed="{{ $day['protein'] === true ? 'true' : 'false' }}"><x-ic n="check" /> {{ $day['protein'] === true ? 'Bati' : 'Bati a meta' }}</button>
          </form>
        </div>
        @endif
        @if (in_array('fvl', $active))
        <a class="row-item px-0 link-row" href="{{ route('habits') }}#vegetais">
          <span class="ic-col t-heading"><x-ic n="carrot" /></span>
          <div class="grow bold">Frutas, verduras e legumes</div>
          <span class="bold tnum t-caption">{{ count(array_filter($day['fvl'])) }}/3</span>
        </a>
        @endif
        @if (in_array('feeling', $active))
        <a class="row-item px-0 link-row" href="{{ route('symptoms') }}">
          <span class="ic-col t-heading"><x-ic n="notes-medical" /></span>
          <div class="grow"><div class="bold">Como você está hoje?</div><div class="t-caption muted">{{ $todaySymptoms->isNotEmpty() ? $todaySymptoms->pluck('symptom')->join(', ') : ($day['feeling'] ? 'Nenhum efeito colateral' : 'Registre efeitos colaterais') }}</div></div>
          <x-ic n="chevron-right" class="muted" />
        </a>
        @endif
      </div>
    </div>
  </x-section>
  @endif

  @if ($nextAppointment || $m['atividade'] || $nextTherapy)
  <x-section title="Cuidado de hoje">
    <div class="stack-2">
      @if ($nextAppointment)
        @php($local = Clock::local($nextAppointment->starts_at))
        <a class="bc-card bc-card--action p-3 flex" href="{{ $m['nutricao'] ? route('nutrition') : route('profile') }}">
          <span class="circle-ic"><x-ic n="calendar-check" /></span>
          <div class="grow"><b>Consulta com {{ $lead }}</b><div class="t-caption muted">{{ Fmt::rel($local) }}, {{ Fmt::time($local) }} · {{ $nextAppointment->type }}</div></div>
          @if ($local->isSameDay(Clock::today()))<x-pill>Hoje</x-pill> @endif
        </a>
      @endif
      @if ($m['atividade'])
        <a class="bc-card bc-card--action p-3 flex" href="{{ route('activity') }}">
          <span class="circle-ic"><x-ic n="dumbbell" /></span>
          <div class="grow"><b>Treino</b><div class="t-caption muted">{{ $todayWorkouts->isNotEmpty() ? $todayWorkouts->map(fn ($w) => $w->modality.' · '.$w->minutes.' min')->join(', ') : 'Nenhum treino registrado hoje' }}</div></div>
          <x-ic n="chevron-right" class="muted" />
        </a>
      @endif
      @if ($nextTherapy)
        @php($local = Clock::local($nextTherapy->starts_at))
        <a class="bc-card bc-card--action p-3 flex" href="{{ route('mental') }}">
          <span class="circle-ic"><x-ic n="brain" /></span>
          <div class="grow"><b>Terapia</b><div class="t-caption muted">{{ Fmt::wd($local) }}, {{ Fmt::short($local) }} às {{ Fmt::time($local) }}</div></div>
          <x-ic n="chevron-right" class="muted" />
        </a>
      @endif
    </div>
  </x-section>
  @endif

  @if ($bio->isNotEmpty())
  <x-section title="Bioimpedância">
    <x-slot:action><a class="bc-link" href="{{ route('bio.index') }}">Histórico <x-ic n="chevron-right" /></a></x-slot:action>
    <div class="grid-2">
      @foreach (['f', 'm', 'v', 't'] as $k)
        @php($ind = Indicators::get($k)) @php($vals = $bio->pluck($k)->all()) @php($t = Indicators::trend($vals, $ind['dir']))
        <a class="bc-card bc-card--action p-3 stack-1" href="{{ route('evolution', ['ind' => $k]) }}">
          <div class="between t-caption bold muted"><span><x-ic :n="$ind['icon']" /> {{ $ind['s'] }}</span><x-spark :values="$vals" :tone="$t['tone']" :w="44" :h="18" /></div>
          <div class="t-num">{{ Fmt::num(end($vals), $ind['dec']) }} <span class="t-caption muted">{{ $ind['u'] }}</span></div>
          @if (count($vals) > 1)<x-delta :d="$t['d']" :ind="$ind" />@endif
        </a>
      @endforeach
    </div>
  </x-section>
  @endif
</div>
@endsection
