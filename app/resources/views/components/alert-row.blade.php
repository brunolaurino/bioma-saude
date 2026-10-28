@props(['alert', 'name', 'compact' => false])
@php use App\Support\Fmt; $sev = $alert->severity; $done = $alert->resolved_at !== null; @endphp
<div class="bc-alert bc-alert--{{ $sev }} {{ $done ? 'bc-alert--done' : '' }}">
  <span class="bc-alert__sev"><x-ic :n="$sev === 'bad' ? 'triangle-exclamation' : 'circle-exclamation'" /></span>
  <div class="bc-alert__body">
    <div><b>{{ $name }}</b> <span class="bc-alert__meta">· {{ Fmt::rel($alert->day) }}</span></div>
    <div class="flex gap-2 wrap"><span>{{ $alert->summary }}</span><x-pill :tone="$sev" icon="circle">{{ $sev === 'bad' ? 'Alerta' : 'Atenção' }}</x-pill></div>
  </div>
  @unless ($compact)
  <div class="flex gap-2">
    <a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ route('pro.patient', [$alert->patient_id, 'aba' => 'sintomas']) }}"><x-ic n="folder-open" /> Prontuário</a>
    @if ($done)
      <span class="t-caption bold c-ok flex gap-1"><x-ic n="check" /> Revisado</span>
    @else
      <form method="POST" action="{{ route('pro.alerts.resolve', $alert) }}">@csrf
        <button class="bc-btn bc-btn--primary bc-btn--sm"><x-ic n="check" /> Marcar revisado</button>
      </form>
    @endif
  </div>
  @endunless
</div>
