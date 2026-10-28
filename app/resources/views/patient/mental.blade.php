@extends('layouts.simple', ['title' => 'Saúde mental', 'back' => route('care')])
@php use App\Support\{Clock, Fmt}; $done = $past->where('status', 'feita')->count(); @endphp
@section('content')
<p class="t-caption muted">{{ $who }}</p>
<x-section title="Sessões de terapia">
  <x-slot:action><span class="t-caption bold tnum">{{ $done }}/{{ $past->count() }} realizadas</span></x-slot:action>
  <div class="bc-card divide">
    @if ($next)
      @php($l = Clock::local($next->starts_at))
      <div class="row-item surface-soft"><span class="circle-ic"><x-ic n="calendar" /></span><div class="grow"><b>Próxima sessão</b><div class="t-caption muted">{{ Fmt::wdl($l) }}, {{ Fmt::short($l) }} às {{ Fmt::time($l) }}</div></div></div>
    @endif
    @foreach ($past as $t)
      @php($l = Clock::local($t->starts_at))
      <div class="row-item">
        <span class="t-caption muted tnum w-14">{{ Fmt::wd($l) }} {{ Fmt::dm($l) }}</span>
        <span class="grow">{{ $t->status === 'feita' ? 'Sessão realizada' : ($t->status === 'faltou' ? 'Não fiz a sessão' : 'Sessão sem confirmação') }}</span>
        <form method="POST" action="{{ route('therapy.toggle', $t) }}">@csrf
          <button class="mark {{ $t->status === 'feita' ? 'mark--ok' : 'mark--warn' }}" aria-pressed="{{ $t->status === 'feita' ? 'true' : 'false' }}" aria-label="{{ $t->status === 'feita' ? 'Marcar como não feita' : 'Marcar como feita' }}"><x-ic :n="$t->status === 'feita' ? 'check' : 'xmark'" /></button>
        </form>
      </div>
    @endforeach
    @if (! $next && $past->isEmpty())<p class="p-3 muted t-body-sm">Nenhuma sessão registrada.</p>@endif
  </div>
</x-section>
<x-section title="Humor nas últimas 4 semanas"><x-mood-calendar :journal="$journal" /></x-section>
<x-section title="Espaço de desabafo">
  <x-slot:action><a class="bc-link" href="{{ route('journal.create') }}"><x-ic n="pen-to-square" /> Escrever</a></x-slot:action>
  <a class="bc-card bc-card--dashed bc-card--action p-4 flex muted" href="{{ route('journal.create') }}"><span class="t-heading"><x-ic n="feather" /></span> Como você está se sentindo com o trabalho, a rotina e as emoções?</a>
  <ul class="plain stack-2 mt-2">
    @foreach ($journal as $j)<li><x-journal-card :j="$j" :href="route('journal.show', $j)" :lead="$lead" /></li>@endforeach
  </ul>
</x-section>
@endsection
