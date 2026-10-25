@extends('layouts.pro', ['title' => 'Auditoria', 'sub' => 'Quem acessou ou mudou o quê'])
@php use App\Support\{Clock, Fmt}; @endphp
@section('actions')
<a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ route('pro.audit', array_filter(['verificar' => 1, 'paciente' => request('paciente'), 'quem' => $who])) }}"><x-ic n="shield-halved" /> <span class="sm-hide">Verificar integridade</span></a>
@endsection
@section('content')
@if ($broken === 0)
  <div class="notice notice--ok"><x-ic n="circle-check" class="ic" /><div><b>Trilha íntegra</b>Nenhum registro foi alterado ou apagado. Cada linha confere com a anterior.</div></div>
@elseif ($broken)
  <div class="notice notice--bad"><x-ic n="triangle-exclamation" class="ic" /><div><b>A corrente quebrou no registro {{ $broken }}</b>Algum registro a partir deste foi alterado ou apagado fora do sistema. Registre um incidente e acione o suporte.</div></div>
@endif

<form method="GET" action="{{ route('pro.audit') }}" class="flex wrap">
  @if ($patientFilter)<input type="hidden" name="paciente" value="{{ $patientFilter->id }}"><span class="bc-chip" aria-current="true">{{ $patientFilter->name }} <a href="{{ route('pro.audit', array_filter(['quem' => $who])) }}" aria-label="Tirar filtro de paciente"><x-ic n="xmark" /></a></span>@endif
  <label class="sr-only" for="a-who">Profissional</label>
  <select id="a-who" name="quem" class="select w-auto" data-autosubmit><option value="">Todas as pessoas</option>@foreach ($people as $id => $n)<option value="{{ $id }}" @selected($who === $id)>{{ $n }}</option>@endforeach</select>
  <noscript><button class="bc-btn bc-btn--secondary bc-btn--sm">Filtrar</button></noscript>
</form>

<div class="table-wrap">
  <table class="data">
    <thead><tr><th>Quando</th><th>Quem</th><th>O quê</th><th>Paciente</th><th>IP</th></tr></thead>
    <tbody>
      @forelse ($rows as $r)
        @php($at = Clock::local(\Carbon\CarbonImmutable::parse($r->occurred_at, 'UTC')))
        <tr>
          <td class="t-caption tnum nowrap">{{ Fmt::dmy($at) }} {{ Fmt::time($at) }}</td>
          <td class="t-body-sm">{{ $r->actor }}</td>
          <td class="t-body-sm">{{ $label($r->action) }}</td>
          <td class="t-body-sm">@if ($r->patient_id)<a href="{{ route('pro.audit', array_filter(['paciente' => $r->patient_id, 'quem' => $who])) }}">{{ $patients[$r->patient_id]->name ?? 'Paciente removido' }}</a>@else<span class="muted">—</span>@endif</td>
          <td class="t-caption muted tnum">{{ $r->ip ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhum registro.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="between">
  @if ($rows->previousPageUrl())<a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ $rows->previousPageUrl() }}"><x-ic n="arrow-left" /> Mais recentes</a>@else<span></span>@endif
  <span class="t-caption muted">Página {{ $rows->currentPage() }} de {{ max(1, $rows->lastPage()) }}</span>
  @if ($rows->nextPageUrl())<a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ $rows->nextPageUrl() }}">Mais antigos <x-ic n="arrow-right" /></a>@else<span></span>@endif
</div>
<p class="privacy-line"><x-ic n="lock" /> A trilha guarda só o que foi acessado, nunca o conteúdo clínico. Ninguém, nem o master, consegue apagar ou editar registros.</p>
@endsection
