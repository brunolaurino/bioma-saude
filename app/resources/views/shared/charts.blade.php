{{-- Painel de gráficos de bioimpedância. $charts vem de EvolutionController::charts; $url(array $query) monta os links. --}}
@php use App\Domain\Indicators; use App\Support\Fmt; $bio = $charts['bio']; $ind = $charts['ind']; $k = $charts['k']; @endphp
@if ($bio->isEmpty())
  <x-empty icon="weight-scale" title="Nenhuma bioimpedância ainda" text="Os gráficos aparecem depois do primeiro registro.">
    @unless ($pro ?? false)<a class="bc-btn bc-btn--primary mt-2" href="{{ route('bio.create') }}"><x-ic n="plus" /> Registrar</a>@endunless
  </x-empty>
@else
  @php
    $vals = $bio->pluck($k)->all(); $t = Indicators::trend($vals, $ind['dir']);
    $total = end($vals) - $vals[0]; $tone = Indicators::toneOfDelta($total, $ind['dir']);
  @endphp
  <div class="stack-4">
    <nav class="chips-scroll" aria-label="Indicador">
      @foreach (Indicators::ALL as $key => $i)
        <a class="bc-chip" href="{{ $url(['ind' => $key, 'periodo' => $charts['period']]) }}" @if($key === $k) aria-current="true" @endif><x-ic :n="$i['icon']" /> {{ $i['s'] }}</a>
      @endforeach
    </nav>
    <div class="bc-card p-3 stack-2">
      <div class="between flex-start">
        <div><div class="t-caption bold muted">{{ $ind['l'] }}</div><div class="t-title-lg tnum">{{ Fmt::num(end($vals), $ind['dec']) }} <span class="t-body-sm muted">{{ $ind['u'] }}</span></div></div>
        <x-pill :tone="$tone" />
      </div>
      <x-line-chart :values="$vals" :labels="$bio->map(fn ($b) => Fmt::dm($b['d']))->all()" :goal="$charts['goal']" :ind="$ind" :tone="$tone" />
      <div class="bc-seg max-seg" role="group" aria-label="Período">
        @foreach (['3m' => '3 meses', '6m' => '6 meses', 'all' => 'Tudo'] as $p => $l)
          <a class="bc-seg-link" href="{{ $url(['ind' => $k, 'periodo' => $p]) }}" @if($p === $charts['period']) aria-current="true" @endif>{{ $l }}</a>
        @endforeach
      </div>
    </div>
    <div class="grid-3 center">
      <div class="bc-card p-2"><div class="t-overline muted">Início</div><div class="bold tnum">{{ Fmt::num($vals[0], $ind['dec']) }}</div><div class="t-caption muted">{{ $ind['u'] }}</div></div>
      <div class="bc-card p-2"><div class="t-overline muted">Atual</div><div class="bold tnum">{{ Fmt::num(end($vals), $ind['dec']) }}</div><div class="t-caption muted">{{ $ind['u'] }}</div></div>
      <div class="bc-card p-2"><div class="t-overline muted">Variação</div><div><x-delta :d="$total" :ind="$ind" /></div><div class="t-caption muted">no período</div></div>
    </div>
    <div class="legend">
      <span><i class="dot-ok"></i>Progresso</span><span><i class="dot-warn"></i>Estável</span><span><i class="dot-bad"></i>Atenção</span>
      @if (count($vals) > 1)<span>· Último registro: <x-delta :d="$t['d']" :ind="$ind" /></span>@endif
    </div>
    <x-section title="Todos os indicadores">
      <div class="bc-card divide">
        @foreach (Indicators::ALL as $key => $i)
          @php($v = $bio->pluck($key)->all()) @php($tt = Indicators::toneOfDelta(end($v) - $v[0], $i['dir']))
          <a class="row-item link-row {{ $key === $k ? 'is-current' : '' }}" href="{{ $url(['ind' => $key, 'periodo' => $charts['period']]) }}">
            <span class="ic-col"><x-ic :n="$i['icon']" /></span>
            <span class="grow truncate">{{ $i['s'] }}</span>
            <x-spark :values="$v" :tone="$tt" :w="60" :h="22" />
            <b class="tnum w-14 right">{{ Fmt::num(end($v), $i['dec']) }}</b>
            <span class="c-{{ $tt }}" title="{{ Indicators::toneLabel($tt) }}"><x-ic :n="Indicators::TONE[$tt]['icon']" /><span class="sr-only">{{ Indicators::toneLabel($tt) }}</span></span>
          </a>
        @endforeach
      </div>
    </x-section>
  </div>
@endif
