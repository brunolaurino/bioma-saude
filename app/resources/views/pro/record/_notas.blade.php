@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
<div class="lg-grid-side stack-6-mobile">
  <div class="stack-6">
    @if ($canWrite('notas'))
    <form method="POST" action="{{ route('pro.patient.note', $patient) }}" class="stack-3" novalidate>
      @csrf
      <x-textarea name="text" label="Nova anotação clínica (só a equipe vê)" rows="4" placeholder="Ex.: Ajustar lanche da tarde. Reavaliar enjoo no próximo retorno." />
      <button class="bc-btn bc-btn--primary"><x-ic n="floppy-disk" /> Salvar anotação</button>
    </form>
    @endif
    <x-section title="Anotações">
      @forelse ($notes as $n)
        <div class="bc-card p-3 stack-1"><div class="t-caption muted">{{ Fmt::dmy(\App\Support\Clock::local($n->created_at)) }} · {{ $names[$n->author_id] ?? 'Equipe' }}</div><p class="prose t-body-sm">{{ $n->text }}</p></div>
      @empty
        <p class="muted t-body-sm">Nenhuma anotação ainda.</p>
      @endforelse
    </x-section>
  </div>
  <div class="stack-6">
    @if ($canWrite('notas'))
    <x-section title="Registrar consulta">
      <form method="POST" action="{{ route('pro.patient.consultation', $patient) }}" class="stack-3" novalidate>
        @csrf
        <div class="bc-field">
          <label class="bc-field__label" for="c-type">Tipo</label>
          <select id="c-type" name="type" class="select">@foreach (Catalog::APPOINTMENT_TYPES as $t)<option @selected(old('type', 'Retorno') === $t)>{{ $t }}</option>@endforeach</select>
        </div>
        <x-field name="day" label="Data" icon="calendar" inputmode="numeric" :value="Fmt::dmy(\App\Support\Clock::today())" id="c-day" />
        <x-textarea name="note" label="Resumo (o paciente vê no histórico de nutrição)" rows="3" id="c-note" />
        <button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="calendar-check" /> Registrar consulta</button>
      </form>
    </x-section>
    @endif
    <x-section title="Consultas">
      @if ($consults->isEmpty())
        <p class="muted t-body-sm">Nenhuma consulta registrada.</p>
      @else
        <ol class="consult-list">
          @foreach ($consults as $c)
            <li><b>{{ Fmt::dmy($c->day) }}</b> · {{ $c->type }}<div class="t-caption muted">{{ $names[$c->professional_id] ?? '' }}</div>@if ($c->note)<p class="t-body-sm mt-1">{{ $c->note }}</p>@endif</li>
          @endforeach
        </ol>
      @endif
    </x-section>
  </div>
</div>
