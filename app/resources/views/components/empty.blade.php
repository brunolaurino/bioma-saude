@props(['icon', 'title', 'text' => null])
<div class="bc-empty">
  <span class="bc-empty__icon"><x-ic :n="$icon" /></span>
  <div class="bc-empty__title">{{ $title }}</div>
  @if ($text)<p class="bc-empty__text">{{ $text }}</p>@endif
  {{ $slot }}
</div>
