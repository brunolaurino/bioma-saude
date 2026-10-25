@props(['title' => null])
<section {{ $attributes->merge(['class' => 'bc-section']) }}>
  @if ($title || isset($action))
  <div class="bc-section__head">
    @if ($title)<h2 class="bc-section__title">{{ $title }}</h2>@endif
    {{ $action ?? '' }}
  </div>
  @endif
  {{ $slot }}
</section>
