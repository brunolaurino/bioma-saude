@extends('layouts.pro', ['title' => 'Incidentes de segurança', 'sub' => 'Registro exigido pela ANPD'])
@php use App\Support\{Clock, Fmt}; @endphp
@section('content')
<div class="lg-grid-side stack-6-mobile">
  <x-section title="Registro">
    @forelse ($list as $i)
      <article class="bc-card p-4 stack-2">
        <div class="flex wrap"><b class="grow">{{ Fmt::dmy(Clock::local($i->detected_at)) }}</b><x-pill :tone="['relevante' => 'bad', 'baixo' => 'ok', 'avaliar' => 'warn'][$i->risk]">Risco {{ $i->risk === 'avaliar' ? 'em avaliação' : $i->risk }}</x-pill></div>
        <p class="prose t-body-sm">{{ $i->description }}</p>
        <div class="t-caption muted">{{ $i->affected_count }} {{ Fmt::plural($i->affected_count, 'titular afetado', 'titulares afetados') }} · ANPD {{ $i->anpd_notified_at ? 'comunicada' : 'não comunicada' }} · pacientes {{ $i->patients_notified_at ? 'avisados' : 'não avisados' }} · registrado por {{ $names[$i->recorded_by] ?? '—' }}</div>
      </article>
    @empty
      <x-empty icon="shield-halved" title="Nenhum incidente registrado" text="Bom sinal. Se algo acontecer, registre aqui mesmo que o risco pareça baixo." />
    @endforelse
  </x-section>
  <x-section title="Registrar incidente">
    <form method="POST" action="{{ route('pro.incidents.store') }}" class="bc-card p-4 stack-3" novalidate>
      @csrf
      <x-field name="detected_on" label="Quando foi descoberto" icon="calendar" inputmode="numeric" :value="Fmt::dmy(Clock::today())" />
      <x-textarea name="description" label="O que aconteceu" rows="4" hint="Descreva o fato, sem colar dados de pacientes." />
      <x-field name="affected_count" label="Quantos titulares podem ter sido afetados" icon="users" inputmode="numeric" value="0" />
      <div class="bc-field">
        <label class="bc-field__label" for="i-risk">Risco aos titulares</label>
        <select id="i-risk" name="risk" class="select"><option value="avaliar">Em avaliação</option><option value="baixo">Baixo</option><option value="relevante">Relevante</option></select>
        <span class="bc-field__hint">Risco relevante: comunique a ANPD e os titulares em até 3 dias úteis (Resolução CD/ANPD 15/2024).</span>
      </div>
      <label class="check"><input type="checkbox" name="anpd_notified" value="1"> <span>A ANPD já foi comunicada</span></label>
      <label class="check"><input type="checkbox" name="patients_notified" value="1"> <span>Os pacientes afetados já foram avisados</span></label>
      <button class="bc-btn bc-btn--primary"><x-ic n="floppy-disk" /> Registrar</button>
    </form>
  </x-section>
</div>
@endsection
