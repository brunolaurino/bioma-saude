@props(['value' => 0, 'tone' => null, 'sm' => false, 'label' => null])
@php($v = max(0, min(100, (float) $value)))
<svg class="bar {{ $tone }} {{ $sm ? 'bar--sm' : '' }}" viewBox="0 0 100 8" preserveAspectRatio="none" role="img" aria-label="{{ $label ?? round($v).'%' }}">
  <rect class="track" x="0" y="0" width="100" height="8" rx="4" />
  @if ($v > 0)<rect class="fill" x="0" y="0" width="{{ $v }}" height="8" rx="4" />@endif
</svg>
