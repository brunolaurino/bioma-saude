@props(['title', 'sub' => null, 'back' => null])
<header class="bc-header">
  @if ($back)
    <a class="bc-iconbtn" href="{{ $back }}" aria-label="Voltar"><x-ic n="arrow-left" /></a>
  @else
    <span class="spacer"></span>
  @endif
  {{ $left ?? '' }}
  <div class="bc-header__text">
    <h1 class="bc-header__title">{{ $title }}</h1>
    @if ($sub)<div class="bc-header__sub">{{ $sub }}</div>@endif
  </div>
  {{ $right ?? '' }}
</header>
