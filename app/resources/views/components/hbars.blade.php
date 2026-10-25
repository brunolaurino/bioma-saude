@props(['rows'])
@php($max = max(1, collect($rows)->max('v')))
<ul class="bc-hbars">
  @foreach ($rows as $r)
    <li><span class="truncate">{{ $r['l'] }}</span><x-bar :value="$r['v'] / $max * 100" :tone="$r['tone'] ?? null" :label="$r['l'].': '.$r['v']" /><b class="tnum right">{{ $r['v'] }}</b></li>
  @endforeach
</ul>
