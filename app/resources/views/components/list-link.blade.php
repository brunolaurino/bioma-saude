@props(['href', 'icon', 'label', 'hint' => null, 'danger' => false])
<a href="{{ $href }}" class="bc-listrow {{ $danger ? 'bc-listrow--danger' : '' }}">
  <span class="bc-listrow__icon"><x-ic :n="$icon" /></span>
  <span class="bc-listrow__text">{{ $label }}@if ($hint)<span class="bc-listrow__hint">{{ $hint }}</span>@endif</span>
  @unless ($danger)<span class="bc-listrow__chev"><x-ic n="chevron-right" /></span>@endunless
</a>
