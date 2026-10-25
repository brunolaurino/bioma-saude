@props(['values', 'labels', 'goal' => null, 'ind', 'tone' => 'ok', 'h' => 180])
@php
  use App\Support\Fmt;
  $W = 340; $H = $h; $pl = 44; $pr = 16; $pt = 22; $pb = 24;
  $vals = array_values($values); $n = count($vals);
  $all = $goal !== null ? array_merge($vals, [$goal]) : $vals;
  $dmin = min($all); $dmax = max($all); $span = ($dmax - $dmin) ?: 1;
  $lo = $dmin - $span * 0.12; $hi = $dmax + $span * 0.12;
  $x = fn ($i) => $pl + ($n === 1 ? ($W - $pl - $pr) / 2 : ($i * ($W - $pl - $pr)) / ($n - 1));
  $y = fn ($v) => $pt + (1 - ($v - $lo) / ($hi - $lo)) * ($H - $pt - $pb);
  $path = implode(' ', array_map(fn ($v, $i) => ($i ? 'L' : 'M').round($x($i), 1).','.round($y($v), 1), $vals, array_keys($vals)));
  $area = $path.' L'.round($x($n - 1), 1).','.($H - $pb).' L'.round($x(0), 1).','.($H - $pb).' Z';
  $every = $n > 8 ? 2 : 1; $last = $vals[$n - 1];
  $summary = $ind['l'].': de '.Fmt::num($vals[0], $ind['dec']).' para '.Fmt::num($last, $ind['dec']).' '.$ind['u'].' em '.$n.' '.Fmt::plural($n, 'registro', 'registros').($goal !== null ? '. Meta: '.Fmt::num($goal, $ind['dec']).' '.$ind['u'] : '').'.';
@endphp
<svg class="chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $summary }}">
  @foreach ([$dmin, ($dmin + $dmax) / 2, $dmax] as $t)
    <line class="bc-chart-grid" x1="{{ $pl }}" x2="{{ $W - $pr }}" y1="{{ round($y($t), 1) }}" y2="{{ round($y($t), 1) }}" />
    <text class="bc-chart-axis" x="{{ $pl - 6 }}" y="{{ round($y($t), 1) }}" text-anchor="end" dominant-baseline="central">{{ Fmt::num($t, $ind['dec']) }}</text>
  @endforeach
  @if ($goal !== null)
    <line class="bc-chart-goal" x1="{{ $pl }}" x2="{{ $W - $pr }}" y1="{{ round($y($goal), 1) }}" y2="{{ round($y($goal), 1) }}" />
    <text class="bc-chart-goal-label" x="{{ $W - $pr }}" y="{{ round($y($goal) - 5, 1) }}" text-anchor="end">meta {{ Fmt::num($goal, $ind['dec']) }} {{ $ind['u'] }}</text>
  @endif
  <path class="area tone-{{ $tone }}" d="{{ $area }}" />
  <path class="bc-chart-line tone-{{ $tone }}" d="{{ $path }}" />
  @foreach ($vals as $i => $v)
    <circle class="dot tone-{{ $tone }} {{ $i === $n - 1 ? 'last' : '' }}" cx="{{ round($x($i), 1) }}" cy="{{ round($y($v), 1) }}" r="{{ $i === $n - 1 ? 4.5 : 2.75 }}" />
  @endforeach
  <text class="bc-chart-value lbl-{{ $tone }}" x="{{ round(min($x($n - 1), $W - $pr - 4), 1) }}" y="{{ round($y($last) - 9, 1) }}" text-anchor="end">{{ Fmt::num($last, $ind['dec']) }}</text>
  @foreach ($labels as $i => $l)
    @if ($i % $every === 0 || $i === $n - 1)
      <text class="bc-chart-axis" x="{{ round($x($i), 1) }}" y="{{ $H - 6 }}" text-anchor="middle">{{ $l }}</text>
    @endif
  @endforeach
</svg>
