@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
<div class="lg-grid-side stack-6-mobile">
  <x-section title="Linha do tempo (30 dias)">
    @if ($symptoms->isEmpty())
      <x-empty icon="face-smile" title="Sem efeitos colaterais" text="Nenhum sintoma registrado nos últimos 30 dias." />
    @else
      <div class="bc-card divide">
        @foreach ($symptoms as $s)
          @php($al = $alertsBySource[$s->id] ?? null) @php($tone = Catalog::INT_TONE[$s->intensity])
          <div class="row-item flex-start">
            <span class="sev-dot dot-{{ $tone }} mt-1" aria-hidden="true"></span>
            <div class="grow">
              <div class="flex wrap gap-2"><b>{{ $s->symptom }}</b><x-pill :tone="$tone" icon="circle">{{ $s->intensity }}</x-pill> <span class="t-caption muted">{{ Fmt::wd($s->day) }}, {{ Fmt::dm($s->day) }} · {{ Fmt::rel($s->day) }}</span></div>
              @if ($s->note)<p class="t-caption muted mt-1">“{{ $s->note }}”</p>@endif
            </div>
            @if ($al && ! $al->resolved_at)
              <form method="POST" action="{{ route('pro.alerts.resolve', $al) }}">@csrf<button class="bc-btn bc-btn--primary bc-btn--sm"><x-ic n="check" /> Revisado</button></form>
            @elseif ($al)
              <span class="t-caption bold c-ok flex gap-1"><x-ic n="check" /> Revisado</span>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </x-section>
  <div class="stack-6">
    <x-section title="Frequência (30 dias)">
      <div class="bc-card p-4">@if ($freq->isEmpty())<p class="muted t-body-sm">Nenhum sintoma registrado.</p>@else<x-hbars :rows="$freq" />@endif</div>
    </x-section>
    <div class="bc-card p-3 stack-2 t-caption muted">
      <div class="flex gap-2"><x-pill tone="bad" icon="circle">Forte</x-pill> gera alerta vermelho na hora</div>
      <div class="flex gap-2"><x-pill tone="warn" icon="circle">Moderada</x-pill> gera alerta amarelo</div>
      <div class="flex gap-2"><x-pill tone="ok" icon="circle">Leve</x-pill> fica só no relatório</div>
    </div>
  </div>
</div>
