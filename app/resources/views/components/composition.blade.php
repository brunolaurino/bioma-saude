@props(['rec', 'prev' => null])
@php use App\Domain\Indicators; use App\Support\Fmt; @endphp
<div class="bc-card">
  <div class="divide">
  @foreach (Indicators::ALL as $k => $ind)
    @php
      [$lo, $hi, $smin, $smax] = Indicators::RANGES[$k];
      $pos = fn ($v) => max(0, min(100, (($v - $smin) / ($smax - $smin)) * 100));
      $v = (float) $rec[$k]; $tone = Indicators::rangeTone($k, $v);
    @endphp
    <div class="range">
      <span class="bold"><x-ic :n="$ind['icon']" class="c-primary" /> {{ $ind['s'] }}</span>
      <svg viewBox="0 0 100 20" preserveAspectRatio="none" role="img" aria-label="{{ $ind['s'] }} {{ Fmt::num($v, $ind['dec']) }} {{ $ind['u'] }}, {{ $tone === 'ok' ? 'dentro da faixa' : ($tone === 'warn' ? 'perto do limite' : 'fora da faixa') }}">
        <rect class="zone-out" x="0" y="6" width="100" height="8" rx="2" />
        <rect class="zone-in" x="{{ $pos($lo) }}" y="6" width="{{ max(0, $pos($hi) - $pos($lo)) }}" height="8" />
        @if ($prev)<rect class="prev" x="{{ $pos((float) $prev[$k]) - 0.4 }}" y="2" width="0.8" height="16" />@endif
        <rect class="mk {{ $tone }}" x="{{ $pos($v) - 1.6 }}" y="0" width="3.2" height="20" rx="1" />
      </svg>
      <span class="right bold tnum c-{{ $tone }}">{{ Fmt::num($v, $ind['dec']) }} {{ $ind['u'] === 'nível' ? '' : $ind['u'] }}</span>
    </div>
  @endforeach
  </div>
  <p class="t-caption muted mt-2">Faixa verde: referência saudável genérica. Traço cinza: registro anterior. A avaliação é da sua equipe.</p>
</div>
