@props(['icon', 'label', 'value', 'hint' => null, 'tone' => null, 'href' => null])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="bc-card {{ $href ? 'bc-card--action' : '' }} bc-kpi">
  <span class="bc-kpi__label"><span>{{ $label }}</span><span class="{{ $tone ? 'c-'.$tone : 'c-primary' }}"><x-ic :n="$icon" /></span></span>
  <span class="bc-kpi__value {{ $tone ? 'c-'.$tone : '' }}">{{ $value }}</span>
  @if ($hint)<span class="bc-kpi__hint">{{ $hint }}</span>@endif
</{{ $tag }}>
