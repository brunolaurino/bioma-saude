@extends('layouts.patient', ['title' => 'Metas diárias'])
@php use App\Domain\Catalog; use App\Support\Fmt; $isToday = $offset === 0; @endphp
@section('tab', 'habits')
@section('header')
<x-header title="Metas diárias" sub="Seu acompanhamento do dia">
  @if ($m['metas'])<x-slot:right><a class="bc-iconbtn" href="{{ route('profile.goals') }}" aria-label="Ver minhas metas"><x-ic n="sliders" /></a></x-slot:right> @endif
</x-header>
@endsection
@section('content')
<div class="screen">
  <nav class="grid-7" aria-label="Dias da semana">
    @foreach ($week as $w)
      <a class="day" href="{{ route('habits', $w['offset'] ? ['dia' => $w['offset']] : []) }}" @if($w['offset'] === $offset) aria-current="date" @endif>
        <span class="t-caption">{{ $w['offset'] === 0 ? 'Hoje' : Fmt::wd($w['date']) }}</span>
        <b>{{ $w['date']->day }}</b>
        <span class="pips" aria-label="{{ $w['done'] }} de {{ $total }} metas">@for ($k = 0; $k < $total; $k++)<i @class(['on' => $k < $w['done']])></i>@endfor</span>
      </a>
    @endforeach
  </nav>

  @if (! $isToday)
    <p class="t-caption muted"><x-ic n="clock-rotate-left" /> Resumo de {{ Fmt::wdl($selected) }}, {{ Fmt::dm($selected) }}. Só o dia de hoje pode ser editado.</p>
    @php
      $fvlN = count(array_filter($day['fvl']));
      $rows = array_filter([
        $m['metas'] && $goalOn['water'] ? ['glass-water', 'Água', $day['water'] >= $goals['water'] ? 'Meta batida' : Fmt::int($day['water']).' ml', $day['water'] >= $goals['water']] : null,
        $m['metas'] && $goalOn['protein'] ? ['drumstick-bite', 'Proteína', $day['protein'] ? 'Meta batida' : 'Não bateu', (bool) $day['protein']] : null,
        $m['metas'] && $goalOn['fvl'] ? ['carrot', 'Frutas, verduras e legumes', $fvlN.' de 3', $fvlN === 3] : null,
        $m['sintomas'] ? ['notes-medical', 'Efeitos colaterais', $symptoms->isNotEmpty() ? $symptoms->map(fn ($s) => $s->symptom.' ('.mb_strtolower($s->intensity).')')->join(', ') : ($day['feeling'] ? 'Nenhum' : 'Sem registro'), $symptoms->isEmpty() && $day['feeling']] : null,
      ]);
    @endphp
    <div class="bc-card divide">
      @foreach ($rows as [$ic, $l, $v, $ok])
        <div class="row-item"><span class="ic-col"><x-ic :n="$ic" /></span><span class="grow">{{ $l }}</span><span class="t-caption bold c-{{ $ok ? 'ok' : 'warn' }} right"><x-ic :n="$ok ? 'circle-check' : 'circle-minus'" /> {{ $v }}</span></div>
      @endforeach
    </div>
    <a class="bc-btn bc-btn--secondary bc-btn--block" href="{{ route('habits') }}"><x-ic n="calendar-day" /> Voltar para hoje</a>
  @else
    @if ($m['metas'] && $goalOn['water'])
      @php($cups = (int) ceil($goals['water'] / 250))
      <div class="bc-card p-4 stack-3" id="agua">
        <div class="between">
          <h2 class="t-heading"><x-ic n="glass-water" /> Água</h2>
          @if ($day['water'] >= $goals['water'])<x-pill tone="ok" icon="circle-check">Meta batida</x-pill> @else<span class="t-caption muted tnum">faltam {{ Fmt::int($goals['water'] - $day['water']) }} ml</span>@endif
        </div>
        <div class="t-num-xl">{{ Fmt::int($day['water']) }} <span class="t-body-sm muted">/ {{ Fmt::int($goals['water']) }} ml</span></div>
        <form method="POST" action="{{ route('habits.water') }}" class="grid-10">
          @csrf
          @for ($i = 0; $i < $cups; $i++)
            @php($full = $day['water'] >= ($i + 1) * 250)
            <button name="set" value="{{ $full && $day['water'] < ($i + 2) * 250 ? $i * 250 : ($i + 1) * 250 }}" @class(['cup', 'full' => $full]) aria-label="{{ Fmt::int(($i + 1) * 250) }} ml"><x-ic n="droplet" /></button>
          @endfor
        </form>
        <div class="grid-3">
          <form method="POST" action="{{ route('habits.water') }}">@csrf<input type="hidden" name="add" value="-250"><button class="bc-btn bc-btn--secondary bc-btn--block" @disabled($day['water'] <= 0)><x-ic n="minus" /> 250</button></form>
          <form method="POST" action="{{ route('habits.water') }}">@csrf<input type="hidden" name="add" value="250"><button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="plus" /> 250 ml</button></form>
          <form method="POST" action="{{ route('habits.water') }}">@csrf<input type="hidden" name="add" value="500"><button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="bottle-water" /> 500</button></form>
        </div>
      </div>
    @endif

    @if ($m['metas'] && $goalOn['protein'])
      <div class="bc-card p-4 stack-3" id="proteina">
        <div class="between"><h2 class="t-heading"><x-ic n="drumstick-bite" /> Proteína</h2><span class="t-caption muted">meta {{ $goals['protein'] }} g</span></div>
        <p class="t-caption muted">Você bateu a meta de proteína hoje?</p>
        <form method="POST" action="{{ route('habits.protein') }}" class="grid-2">
          @csrf
          <button name="value" value="{{ $day['protein'] === true ? 'clear' : '1' }}" class="choice choice--ok" aria-pressed="{{ $day['protein'] === true ? 'true' : 'false' }}"><x-ic n="circle-check" class="ic-lg" /> Bati</button>
          <button name="value" value="{{ $day['protein'] === false ? 'clear' : '0' }}" class="choice choice--warn" aria-pressed="{{ $day['protein'] === false ? 'true' : 'false' }}"><x-ic n="circle-xmark" class="ic-lg" /> Não bati</button>
        </form>
      </div>
    @endif

    @if ($m['metas'] && $goalOn['fvl'])
      <div class="bc-card p-4 stack-3" id="vegetais">
        <div class="between"><h2 class="t-heading"><x-ic n="carrot" /> Frutas, verduras e legumes</h2><span class="t-caption bold tnum">{{ count(array_filter($day['fvl'])) }}/3</span></div>
        <form method="POST" action="{{ route('habits.fvl') }}" class="grid-3">
          @csrf
          @foreach (Catalog::FVL as $k => [$l, $ic])
            <button name="item" value="{{ $k }}" class="choice" aria-pressed="{{ $day['fvl'][$k] ? 'true' : 'false' }}"><x-ic :n="$day['fvl'][$k] ? 'circle-check' : $ic" class="ic-lg" /> {{ $l }}</button>
          @endforeach
        </form>
      </div>
    @endif

    @if ($m['sintomas'])
      <div class="bc-card p-4 stack-3">
        <div class="between">
          <h2 class="t-heading"><x-ic n="notes-medical" /> Efeitos colaterais</h2>
          @if ($day['feeling'] || $symptoms->isNotEmpty())
            <x-pill :tone="$symptoms->contains('intensity', 'Forte') ? 'bad' : ($symptoms->isNotEmpty() ? 'warn' : 'ok')">{{ $symptoms->isNotEmpty() ? $symptoms->count().' '.Fmt::plural($symptoms->count(), 'registrado', 'registrados') : 'Tudo bem' }}</x-pill>
          @endif
        </div>
        @if ($symptoms->isNotEmpty())
          <div class="chips">@foreach ($symptoms as $s)<x-pill :tone="Catalog::INT_TONE[$s->intensity]" icon="circle">{{ $s->symptom }} · {{ $s->intensity }}</x-pill> @endforeach</div>
        @else
          <p class="t-caption muted">Dor de cabeça, enjoo, ansiedade? Registrar ajuda {{ $lead }} a ajustar seu tratamento.</p>
        @endif
        <div class="grid-2">
          <form method="POST" action="{{ route('symptoms.fine') }}">@csrf<button class="bc-btn bc-btn--secondary bc-btn--block" @disabled($symptoms->isNotEmpty())><x-ic n="face-smile" :r="true" /> Estou bem</button></form>
          <a class="bc-btn bc-btn--primary bc-btn--block" href="{{ route('symptoms') }}"><x-ic n="plus" /> Registrar</a>
        </div>
      </div>
    @endif
  @endif

  @if ($grid['rows'])
  <x-section title="Últimos 14 dias">
    <div class="bc-card p-3"><x-habit-grid :grid="$grid" /></div>
  </x-section>
  @endif
</div>
@endsection
