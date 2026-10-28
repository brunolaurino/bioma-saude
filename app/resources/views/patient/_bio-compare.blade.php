@php use App\Domain\Indicators; use App\Support\Fmt; @endphp
<div class="bc-card divide">
  @foreach (Indicators::ALL as $k => $ind)
    <div class="row-item">
      <span class="ic-col"><x-ic :n="$ind['icon']" /></span>
      <span class="grow">{{ $full ?? false ? $ind['l'] : $ind['s'] }}</span>
      @if ($prev && ! ($full ?? false))<span class="t-caption muted tnum">{{ Fmt::num($prev[$k], $ind['dec']) }} →</span>@endif
      <b class="tnum">{{ Fmt::num($cur[$k], $ind['dec']) }}{{ ($full ?? false) ? ' '.$ind['u'] : '' }}</b>
      <span class="w-20 right">@if ($prev)<x-delta :d="$cur[$k] - $prev[$k]" :ind="$ind" />@else<span class="t-caption muted">inicial</span>@endif</span>
    </div>
  @endforeach
</div>
