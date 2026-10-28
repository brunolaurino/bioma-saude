@props(['journal'])
@php
  use App\Domain\Catalog; use App\Support\{Clock, Fmt};
  $today = Clock::today();
  $byDay = collect($journal)->groupBy(fn ($j) => $j->day->toDateString())->map(fn ($g) => (int) $g->first()->mood);
  $days = collect(range(27, 0))->map(fn ($i) => $today->subDays($i));
  $vals = $days->map(fn ($d) => $byDay[$d->toDateString()] ?? null)->filter();
  $avg = $vals->count() ? (int) round($vals->avg()) : null;
@endphp
<div class="bc-card stack-2">
  <div class="mood-cal t-caption muted center" aria-hidden="true">@foreach ($days->take(7) as $d)<span>{{ Fmt::wd($d) }}</span>@endforeach</div>
  <div class="mood-cal" role="img" aria-label="Humor nas últimas 4 semanas{{ $avg ? ': média '.Catalog::mood($avg)['l'] : ': sem registros' }}">
    @foreach ($days as $d)
      @php($m = $byDay[$d->toDateString()] ?? null)
      @if ($m)
        <div class="c-{{ Catalog::moodTone($m) }} {{ $d->equalTo($today) ? 'today' : '' }}" title="{{ Fmt::dm($d) }} · {{ Catalog::mood($m)['l'] }}"><x-ic :n="Catalog::mood($m)['icon']" :r="true" /></div>
      @else
        <div class="empty {{ $d->equalTo($today) ? 'today' : '' }}">{{ $d->day }}</div>
      @endif
    @endforeach
  </div>
  <div class="between t-caption muted">
    <span>Média das 4 semanas: <b>{{ $avg ? Catalog::mood($avg)['l'] : 'sem registros' }}</b></span>
    <span class="flex gap-2">@foreach ([5, 3, 1] as $v)<span class="c-{{ Catalog::moodTone($v) }}"><x-ic :n="Catalog::mood($v)['icon']" :r="true" /></span>@endforeach</span>
  </div>
</div>
