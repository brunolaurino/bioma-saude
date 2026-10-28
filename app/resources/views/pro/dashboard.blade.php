@extends('layouts.pro', ['title' => $greeting, 'sub' => \App\Support\Fmt::wdl(\App\Support\Clock::today()).', '.\App\Support\Fmt::dmy(\App\Support\Clock::today())])
@php use App\Support\{Clock, Fmt}; $byName = $byId->map(fn ($p) => $p->name); @endphp
@section('banner')
<section class="pro-banner on-dark">
  <div class="grow">
    <div class="t-caption muted">{{ Fmt::wdl(Clock::today()) }}, {{ Fmt::dmy(Clock::today()) }}</div>
    <h1 class="pro-banner__title">{{ $greeting }}</h1>
    <p class="pro-banner__text">
      @if ($proNav['agenda']){{ $appointments->count() }} {{ Fmt::plural($appointments->count(), 'consulta', 'consultas') }} hoje · @endif
      {{ $alerts->count() }} {{ Fmt::plural($alerts->count(), 'alerta aberto', 'alertas abertos') }}
    </p>
  </div>
  <div class="pro-banner__actions">
    @if ($proNav['agenda'])<a class="bc-btn bc-btn--glass" href="{{ route('pro.agenda') }}"><x-ic n="calendar-days" /> Agenda</a>@endif
    <a class="bc-btn bc-btn--primary" href="{{ route('pro.patients.create') }}"><x-ic n="user-plus" /> Novo paciente</a>
  </div>
</section>
@endsection
@section('content')
<div class="grid-2 lg-grid-4">
  <x-kpi icon="users" label="Pacientes ativos" :value="$rows->count()" :hint="$newThisMonth ? '+'.$newThisMonth.' este mês' : 'Nenhum novo este mês'" :href="route('pro.patients')" />
  <x-kpi icon="triangle-exclamation" label="Alertas abertos" :value="$alerts->count()" :tone="$alerts->count() ? 'bad' : 'ok'" hint="Sintomas, humor e peso" :href="route('pro.alerts')" />
  <x-kpi icon="book-open" label="Desabafos novos" :value="$unread->count()" hint="Para ler antes das consultas" :href="route('pro.journals')" />
  @if ($proNav['agenda'])
    <x-kpi icon="calendar-day" label="Consultas hoje" :value="$appointments->count()" :hint="'Próxima: '.($next ? Fmt::time(Clock::local($next->starts_at)) : '—')" :href="route('pro.agenda')" />
  @else
    <x-kpi icon="calendar-xmark" label="Sem registro" :value="$idle->count()" :tone="$idle->count() ? 'warn' : null" hint="Pacientes parados no app" :href="route('pro.patients', ['filtro' => 'idle'])" />
  @endif
</div>

<div class="lg-grid-2 stack-6-mobile">
  @if ($proNav['agenda'])
  <x-section title="Consultas de hoje">
    <x-slot:action><a class="bc-link" href="{{ route('pro.agenda') }}">Agenda <x-ic n="chevron-right" /></a></x-slot:action>
    @if ($appointments->isEmpty())
      <p class="notice notice--dashed">Nenhuma consulta marcada para hoje.</p>
    @else
      <div class="bc-card divide">
        @foreach ($appointments as $a)
          @php($done = $a->status === 'atendida')
          <div class="row-item {{ $done ? 'is-done' : '' }}">
            <b class="tnum w-12">{{ Fmt::time(Clock::local($a->starts_at)) }}</b>
            <x-avatar :name="$byName[$a->patient_id] ?? '?'" size="sm" />
            <a class="grow link-plain" href="{{ route('pro.patient', $a->patient_id) }}"><b class="truncate block">{{ $byName[$a->patient_id] ?? 'Paciente' }}</b><span class="t-caption muted">{{ $a->type }}</span></a>
            @if ($done)
              <span class="t-caption muted flex gap-1"><x-ic n="check" /> Atendida</span>
            @else
              <a class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('pro.patient.prep', $a->patient_id) }}"><x-ic n="clipboard-list" /> Preparar</a>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </x-section>
  @endif

  <x-section title="Precisa de atenção">
    <x-slot:action><a class="bc-link" href="{{ route('pro.alerts') }}">Todos <x-ic n="chevron-right" /></a></x-slot:action>
    @forelse ($alerts->take(4) as $a)
      <a class="link-plain block" href="{{ route('pro.patient', [$a->patient_id, 'aba' => $a->source_type === 'journal' ? 'desabafos' : ($a->source_type === 'bio' ? 'bio' : 'sintomas')]) }}"><x-alert-row :alert="$a" :name="$byName[$a->patient_id] ?? 'Paciente'" :compact="true" /></a>
    @empty
      <x-empty icon="circle-check" title="Nenhum alerta aberto" />
    @endforelse
  </x-section>

  <x-section title="Desabafos novos">
    <x-slot:action><a class="bc-link" href="{{ route('pro.journals') }}">Ler todos <x-ic n="chevron-right" /></a></x-slot:action>
    @forelse ($unread->take(3) as $j)
      <x-journal-card :j="$j" :href="route('pro.journals', ['abrir' => $j->id])" :patient-name="$byName[$j->patient_id] ?? 'Paciente'" :unread="true" />
    @empty
      <x-empty icon="envelope-open" title="Tudo lido" text="Novos desabafos aparecem aqui quando os pacientes compartilham." />
    @endforelse
  </x-section>

  <x-section :title="'Sem registro há '.\App\Domain\AlertEngine::idleDays().' dias ou mais'">
    @if ($idle->isEmpty())
      <p class="notice notice--ok"><x-ic n="circle-check" class="ic" /> Todos os pacientes registraram algo recentemente.</p>
    @else
      <div class="bc-card divide">
        @foreach ($idle as $r)
          <div class="row-item">
            <x-avatar :name="$r['p']->name" size="sm" />
            <a class="grow link-plain" href="{{ route('pro.patient', $r['p']) }}"><b>{{ $r['p']->name }}</b><div class="t-caption c-warn bold">{{ $r['last'] ? 'Último registro '.Fmt::rel($r['last']) : 'Ainda não registrou nada' }}</div></a>
            <form method="POST" action="{{ route('pro.patient.remind', $r['p']) }}">@csrf<button class="bc-btn bc-btn--secondary bc-btn--sm"><x-ic n="bell" /> Lembrar</button></form>
          </div>
        @endforeach
      </div>
    @endif
  </x-section>
</div>
@endsection
