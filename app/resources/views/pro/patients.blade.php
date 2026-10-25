@extends('layouts.pro', ['title' => 'Pacientes', 'sub' => $active.' '.\App\Support\Fmt::plural($active, 'ativo', 'ativos')])
@php use App\Domain\{Indicators, ProData}; use App\Support\Fmt; use App\Http\Controllers\Pro\PatientsController; @endphp
@section('actions')
<a class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('pro.patients.create') }}"><x-ic n="user-plus" /> <span class="sm-hide">Novo paciente</span></a>
@endsection
@section('content')
<form method="GET" action="{{ route('pro.patients') }}" role="search" class="stack-3">
  <input type="hidden" name="filtro" value="{{ $filter }}">
  <div class="bc-field">
    <label class="sr-only" for="pt-search">Buscar paciente</label>
    <div class="bc-field__box">
      <span class="bc-field__icon"><x-ic n="magnifying-glass" /></span>
      <input id="pt-search" name="q" type="search" value="{{ $q }}" placeholder="Buscar paciente pelo nome" autocomplete="off">
    </div>
  </div>
  <nav class="chips-scroll" aria-label="Filtros">
    @foreach (PatientsController::FILTERS as $k => $l)
      <a class="bc-chip" href="{{ route('pro.patients', array_filter(['filtro' => $k, 'q' => $q])) }}" @if($filter === $k) aria-current="true" @endif>{{ $l }} <span class="muted">{{ $counts[$k] }}</span></a>
    @endforeach
  </nav>
</form>

@if ($list->isEmpty())
  <x-empty icon="user-slash" title="Nenhum paciente encontrado" text="Tente outro nome ou limpe os filtros.">
    <a class="bc-btn bc-btn--secondary mt-2" href="{{ route('pro.patients') }}"><x-ic n="rotate-left" /> Limpar</a>
  </x-empty>
@else
  <div class="table-wrap md-only">
    <table class="data">
      <thead><tr><th>Paciente</th><th>Peso</th><th>Gordura</th><th>Adesão (14 dias)</th><th>Último registro</th><th>Status</th><th><span class="sr-only">Abrir</span></th></tr></thead>
      <tbody>
        @foreach ($list as $r)
          @php($p = $r['p']) @php($href = route('pro.patient', $p))
          <tr>
            <td><a class="row-link flex" href="{{ $href }}"><x-avatar :name="$p->name" size="sm" /><span><b>{{ $p->name }}</b><span class="t-caption muted block">{{ $p->age() ? $p->age().' anos' : 'Idade não informada' }}{{ $p->objective ? ' · '.$p->objective : '' }}</span></span></a></td>
            <td>@if ($r['w'] !== null)<b>{{ Fmt::num($r['w']) }} kg</b>@if ($r['dw'] !== null)<span class="block"><x-delta :d="$r['dw']" :ind="Indicators::ALL['w']" /></span>@endif @else<span class="muted">—</span>@endif</td>
            <td>{{ $r['f'] !== null ? Fmt::num($r['f']).'%' : '—' }}</td>
            <td>@if ($r['adh'] !== null)<div class="flex gap-2 w-36"><x-bar :value="$r['adh']" :tone="ProData::adhTone($r['adh'])" :sm="true" /><b class="t-caption tnum">{{ $r['adh'] }}%</b></div>@else<span class="muted">—</span>@endif</td>
            <td class="t-caption">{{ $r['last'] ? Fmt::rel($r['last']) : ($p->status === 'invited' ? 'Ainda não entrou' : 'Nenhum') }}</td>
            <td><div class="stack-1"><x-pill :tone="$r['status']['tone']">{{ $r['status']['l'] }}</x-pill>@if ($r['unread'])<span class="t-caption bold block"><x-ic n="book-open" /> {{ $r['unread'] }} {{ Fmt::plural($r['unread'], 'desabafo', 'desabafos') }}</span>@endif</div></td>
            <td class="muted"><a class="row-link" href="{{ $href }}" aria-label="Abrir prontuário de {{ $p->name }}"><x-ic n="chevron-right" /></a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <ul class="plain stack-2 md-hide">
    @foreach ($list as $r)
      @php($p = $r['p'])
      <li><a class="bc-card bc-card--action p-3 flex link-plain" href="{{ route('pro.patient', $p) }}">
        <x-avatar :name="$p->name" />
        <div class="grow stack-1">
          <div class="flex gap-2"><b class="truncate">{{ $p->name }}</b>@if ($r['unread'])<x-ic n="book-open" />@endif</div>
          @if ($r['w'] !== null || $r['adh'] !== null)
          <div class="t-caption muted tnum flex gap-2">@if ($r['w'] !== null){{ Fmt::num($r['w']) }} kg @if ($r['dw'] !== null)<x-delta :d="$r['dw']" :ind="Indicators::ALL['w']" />@endif @endif @if ($r['adh'] !== null)· adesão {{ $r['adh'] }}%@endif</div>
          @endif
          <div class="flex gap-2"><x-pill :tone="$r['status']['tone']">{{ $r['status']['l'] }}</x-pill> <span class="t-caption muted">{{ $r['last'] ? Fmt::rel($r['last']) : '' }}</span></div>
        </div>
        <span class="muted"><x-ic n="chevron-right" /></span>
      </a></li>
    @endforeach
  </ul>
@endif
@endsection
