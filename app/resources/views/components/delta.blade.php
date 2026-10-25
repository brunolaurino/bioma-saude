@props(['d', 'ind'])
@php
  $tone = \App\Domain\Indicators::toneOfDelta((float) $d, $ind['dir']);
  $arrow = abs($d) < 1e-9 ? 'equals' : ($d < 0 ? 'arrow-down' : 'arrow-up');
  $word = abs($d) < 1e-9 ? 'sem mudança' : ($d < 0 ? 'menos' : 'mais');
@endphp
<span class="bc-delta bc-delta--{{ $tone }}"><x-ic :n="$arrow" /><span class="sr-only">{{ $word }}</span> {{ \App\Support\Fmt::num(abs($d), $ind['dec']) }} {{ $ind['u'] }}</span>
