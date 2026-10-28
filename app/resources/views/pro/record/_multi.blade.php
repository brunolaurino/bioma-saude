@php use App\Support\{Clock, Fmt}; @endphp
<div class="lg-grid-3 stack-6-mobile">
  @isset($nutrition)
  <x-section title="Nutrição">
    <p class="t-caption muted">{{ $who['nutricao'] }}</p>
    @include('shared.nutrition', $nutrition + ['upload' => $canWrite('nutricao') ? route('pro.patient.plan', $patient) : null])
  </x-section>
  @endisset
  @isset($workouts)
  <x-section title="Atividade física">
    @if ($who['atividade'])<p class="t-caption muted">{{ $who['atividade'] }}</p>@endif
    <div class="grid-2 center">
      @foreach (['Força', 'Aeróbico'] as $c)
        <div class="bc-card p-2"><div class="t-num">{{ $workouts->filter(fn ($w) => $w->category === $c && $w->day->gte(Clock::today()->subDays(6)))->count() }}</div><div class="t-overline muted">{{ $c }} · 7 dias</div></div>
      @endforeach
    </div>
    @if ($workouts->isEmpty())
      <p class="muted t-body-sm">Nenhum treino registrado.</p>
    @else
      <div class="bc-card divide">@foreach ($workouts as $w)<x-workout-row :w="$w" :show-date="true" />@endforeach</div>
    @endif
  </x-section>
  @endisset
  @isset($therapy)
  <x-section title="Saúde mental">
    @if ($who['saude_mental'])<p class="t-caption muted">{{ $who['saude_mental'] }}</p>@endif
    <div class="bc-card divide">
      @forelse ($therapy as $t)
        @php($l = Clock::local($t->starts_at))
        <div class="row-item">
          <span class="t-caption muted tnum w-14">{{ Fmt::dm($l) }}</span><span class="grow">Sessão de terapia <span class="t-caption muted">{{ Fmt::time($l) }}</span></span>
          <x-pill :tone="$t->status === 'feita' ? 'ok' : ($t->status === 'faltou' ? 'warn' : null)" :icon="$t->status === 'feita' ? 'check' : ($t->status === 'faltou' ? 'xmark' : 'clock')">{{ ['feita' => 'Feita', 'faltou' => 'Faltou', 'agendada' => 'Agendada'][$t->status] ?? $t->status }}</x-pill>
        </div>
      @empty
        <p class="p-3 muted t-body-sm">Nenhuma sessão registrada.</p>
      @endforelse
    </div>
    @if ($canWrite('saude_mental'))
      <form method="POST" action="{{ route('pro.patient.therapy', $patient) }}" class="stack-3" novalidate>
        @csrf
        <div class="grid-2 gap-3">
          <x-field name="day" label="Data" icon="calendar" inputmode="numeric" placeholder="dd/mm/aaaa" id="th-day" />
          <x-field name="time" label="Horário" icon="clock" inputmode="numeric" placeholder="19:00" id="th-time" />
        </div>
        <button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="calendar-plus" /> Agendar sessão</button>
      </form>
    @endif
  </x-section>
  @endisset
</div>
