@props(['value', 'total', 'size' => 56])
@php($r = ($size - 8) / 2) @php($c = 2 * M_PI * $r) @php($p = $total ? min(1, $value / $total) : 0)
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" role="img" aria-label="{{ $value }} de {{ $total }} metas" class="none {{ $p >= 1 ? 'bc-ring--done' : '' }}">
  <circle class="bc-ring__track" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" stroke-width="6" />
  <circle class="bc-ring__bar" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" stroke-width="6" stroke-dasharray="{{ round($c * $p, 2) }} {{ round($c, 2) }}" transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})" />
  <text class="bc-ring__text" x="50%" y="50%" dominant-baseline="central" text-anchor="middle">{{ $value }}/{{ $total }}</text>
</svg>
