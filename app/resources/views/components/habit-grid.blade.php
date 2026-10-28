@props(['grid'])
@php use App\Support\Fmt; $from = $grid['from']; @endphp
<div class="stack-2">
  @foreach ($grid['rows'] as $row)
    <div class="hgrid">
      <span class="truncate muted"><x-ic :n="$row['ic']" /> {{ $row['l'] }}</span>
      <div class="cells" role="img" aria-label="{{ $row['l'] }}: {{ collect($row['vals'])->filter(fn ($v) => $v === 1)->count() }} de 14 dias com meta batida">
        @foreach ($row['vals'] as $i => $v)
          @php($cls = match (true) { $v === 1 => 'v1', $v === 0.5 => 'v05', $v === -0.5 => 's05', $v === -1 => 's1', $v === null => 'na', default => '' })
          <span class="{{ $cls }} {{ $i === 13 ? 'today' : '' }}" title="{{ Fmt::dm($from->addDays($i)) }}"></span>
        @endforeach
      </div>
    </div>
  @endforeach
  <div class="hgrid t-caption muted"><span></span><div class="between"><span>{{ Fmt::dm($from) }}</span><span>hoje</span></div></div>
  <div class="legend">
    <span><i class="v1"></i>Meta batida</span><span><i class="v05"></i>Parcial</span><span><i class="v0"></i>Não bateu</span>
    <span><i class="s05"></i>Sintoma leve</span><span><i class="s1"></i>Moderado ou forte</span>
  </div>
</div>
