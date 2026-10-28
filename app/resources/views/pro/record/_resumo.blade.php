@php use App\Domain\{Catalog, Indicators, ProData}; use App\Support\{Clock, Fmt}; @endphp
<div class="flex wrap gap-2 t-caption">
  <x-pill :tone="$row['status']['tone']">{{ $row['status']['l'] }}</x-pill>
  @if ($patient->phone)<span class="muted flex gap-1"><x-ic n="phone" /> <span class="select-all">{{ $patient->phone }}</span></span>@endif
  @if ($next)@php($l = Clock::local($next->starts_at))<span class="muted flex gap-1"><x-ic n="calendar" /> Próxima consulta {{ Fmt::dm($l) }} às {{ Fmt::time($l) }}</span>@endif
  @if ($patient->is_minor)<span class="muted flex gap-1"><x-ic n="user-shield" /> Menor · responsável: {{ $patient->guardian_name }}</span>@endif
</div>

@if ($patient->status === 'invited')
  <div class="notice notice--warn">
    <x-ic n="envelope" class="ic" />
    <div class="grow"><b>{{ $patient->firstName() }} ainda não ativou a conta</b>O código de primeiro acesso vale 7 dias. Se expirou ou se perdeu, gere outro.</div>
    <form method="POST" action="{{ route('pro.patient.invite', $patient) }}">@csrf<button class="bc-btn bc-btn--secondary bc-btn--sm"><x-ic n="rotate" /> Novo código</button></form>
  </div>
@endif

@if ($bio->isNotEmpty())
  <div class="grid-2 lg-grid-5">
    @foreach (Indicators::ALL as $k => $i)
      @php($v = $bio->pluck($k)->all()) @php($tt = Indicators::toneOfDelta(end($v) - $v[0], $i['dir']))
      <a class="bc-card bc-card--action p-3 stack-1 link-plain" href="{{ route('pro.patient', [$patient, 'aba' => 'bio', 'ind' => $k]) }}">
        <span class="between t-caption muted bold"><span><x-ic :n="$i['icon']" /> {{ $i['s'] }}</span><span class="c-{{ $tt }}"><x-ic :n="Indicators::TONE[$tt]['icon']" /></span></span>
        <span class="t-num">{{ Fmt::num(end($v), $i['dec']) }} <span class="t-caption muted">{{ $i['u'] }}</span></span>
        <x-spark :values="$v" :tone="$tt" :w="120" :h="26" />
        <span class="t-caption muted flex gap-1">Desde o início <x-delta :d="end($v) - $v[0]" :ind="$i" /></span>
      </a>
    @endforeach
  </div>
@elseif ($canWrite('bioimpedancia') || isset($m['bioimpedancia']) && $m['bioimpedancia'])
  <p class="notice notice--dashed"><x-ic n="weight-scale" class="ic" /> Nenhuma bioimpedância registrada ainda.</p>
@endif

<div class="md-grid-3 stack-4-mobile">
  @if ($row['adh'] !== null)
  <div class="bc-card p-3 stack-2">
    <div class="t-overline muted">Adesão às metas (14 dias)</div>
    <div class="t-num">{{ $row['adh'] }}%</div>
    <x-bar :value="$row['adh']" :tone="ProData::adhTone($row['adh'])" />
    <a class="bc-link" href="{{ route('pro.patient', [$patient, 'aba' => 'habitos']) }}">Ver hábitos</a>
  </div>
  @endif
  @if (isset($tabs['sintomas']))
  <div class="bc-card p-3 stack-2">
    <div class="t-overline muted">Último sintoma</div>
    @if ($lastSymptom)
      <div><b>{{ $lastSymptom->symptom }}</b> <x-pill :tone="Catalog::INT_TONE[$lastSymptom->intensity]" icon="circle">{{ $lastSymptom->intensity }}</x-pill><div class="t-caption muted mt-1">{{ Fmt::rel($lastSymptom->day) }}</div></div>
    @else
      <div class="muted t-body-sm">Nenhum registro</div>
    @endif
    <a class="bc-link" href="{{ route('pro.patient', [$patient, 'aba' => 'sintomas']) }}">Ver sintomas</a>
  </div>
  @endif
  @if (isset($tabs['desabafos']))
  <div class="bc-card p-3 stack-2">
    <div class="t-overline muted">Último desabafo</div>
    @if ($lastJournal)
      <div class="flex-start t-body-sm"><span class="t-title c-{{ Catalog::moodTone((int) $lastJournal->mood) }}"><x-ic :n="Catalog::mood((int) $lastJournal->mood)['icon']" :r="true" /></span><p class="clamp-3">{{ $lastJournal->text }}</p></div>
    @else
      <div class="muted t-body-sm">Nenhum compartilhado</div>
    @endif
    <a class="bc-link" href="{{ route('pro.patient', [$patient, 'aba' => 'desabafos']) }}">Ler desabafos</a>
  </div>
  @endif
</div>

@if ($bio->isNotEmpty() && $goals['weight'])
  @php($first = $bio->first()['w']) @php($cur = $bio->last()['w']) @php($span = $first - $goals['weight'])
  <div class="bc-card p-3 stack-2">
    <div class="t-overline muted">Peso em relação à meta</div>
    <div class="between t-caption muted tnum"><span>Início {{ Fmt::num($first) }} kg</span><span>Atual {{ Fmt::num($cur) }} kg</span><span>Meta {{ Fmt::num($goals['weight']) }} kg</span></div>
    <x-bar :value="$span != 0 ? (($first - $cur) / $span) * 100 : 0" tone="ok" />
  </div>
@endif

<div class="lg-grid-2 stack-6-mobile">
  <x-section title="Atividade recente">
    @if ($events->isEmpty())
      <p class="muted t-body-sm">Nada registrado ainda.</p>
    @else
      <div class="bc-card p-4">
        <ol class="timeline">
          @foreach ($events as $e)
            <li><span class="dot {{ $e['tone'] === 'ok' ? '' : $e['tone'] }}"><x-ic :n="$e['icon']" :r="$e['r'] ?? false" /></span>
              <div class="t-body-sm">{{ $e['t'] }}</div><div class="t-caption muted">{{ Fmt::wd($e['d']) }}, {{ Fmt::dm($e['d']) }} · {{ Fmt::rel($e['d']) }}</div></li>
          @endforeach
        </ol>
      </div>
    @endif
  </x-section>
  @if ($bio->isNotEmpty())
    <x-section :title="'Composição corporal · '.Fmt::dm($bio->last()['d'])">
      <x-composition :rec="$bio->last()" :prev="$bio->count() > 1 ? $bio[$bio->count() - 2] : null" />
    </x-section>
  @endif
</div>
