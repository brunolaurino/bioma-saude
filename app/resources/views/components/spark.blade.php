@props(['values' => [], 'tone' => 'ink', 'w' => 84, 'h' => 28])
@php
  $vals = array_values($values);
  $n = count($vals);
  $lo = $n ? min($vals) : 0; $hi = $n ? max($vals) : 1; $span = ($hi - $lo) ?: 1;
  $pts = [];
  foreach ($vals as $i => $v) {
    $pts[] = [round(2 + ($i * ($w - 6)) / max(1, $n - 1), 1), round(3 + (1 - ($v - $lo) / $span) * ($h - 6), 1)];
  }
@endphp
@if ($n)
<svg class="spark" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $w }} {{ $h }}" aria-hidden="true">
  <polyline class="{{ $tone }}" points="{{ implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts)) }}" />
  <circle class="{{ $tone }}" cx="{{ end($pts)[0] }}" cy="{{ end($pts)[1] }}" r="2.75" />
</svg>
@endif
