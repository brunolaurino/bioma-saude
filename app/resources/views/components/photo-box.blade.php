@props(['src' => null, 'label' => null, 'icon' => null, 'alt' => ''])
<div {{ $attributes->merge(['class' => 'bc-photo']) }}>
  @if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
  @else
    <svg class="x" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><line x1="0" y1="0" x2="100" y2="100" vector-effect="non-scaling-stroke"/><line x1="100" y1="0" x2="0" y2="100" vector-effect="non-scaling-stroke"/></svg>
    @if ($icon)<span class="t-title-lg"><x-ic :n="$icon" /></span>@endif
    @if (! $label)<span class="sr-only">Sem foto</span>@endif
  @endif
  @if ($label)<span class="bc-photo__label">{{ $label }}</span>@endif
</div>
