@props(['icon', 'label', 'value', 'hint' => null, 'tone' => null, 'href' => null])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="bc-card {{ $href ? 'bc-card--action' : '' }} bc-kpi">
  <span class="kpi-ic {{ $tone ? 'kpi-ic--'.$tone : '' }}"><x-ic :n="$icon" /></span>
  <span class="kpi-txt">
    <span class="bc-kpi__label">{{ $label }}</span>
    <span class="bc-kpi__value {{ $tone ? 'c-'.$tone : '' }}">{{ $value }}</span>
    @if ($hint)<span class="bc-kpi__hint">{{ $hint }}</span>@endif
  </span>
</{{ $tag }}>
