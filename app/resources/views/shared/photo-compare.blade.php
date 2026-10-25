@php use App\Domain\{Catalog, Indicators}; use App\Support\Fmt; @endphp
<form method="GET" action="{{ $action }}" class="stack-4">
  @foreach ($hidden ?? [] as $hk => $hv)<input type="hidden" name="{{ $hk }}" value="{{ $hv }}">@endforeach
  <div class="seg-radio" role="radiogroup" aria-label="Ângulo">
    @foreach (Catalog::ANGLES as $ang)
      <label><input type="radio" name="angulo" value="{{ $ang }}" @checked($angle === $ang) data-autosubmit><span>{{ $ang }}</span></label>
    @endforeach
  </div>
  <div class="grid-2 gap-3">
    @foreach ([['antes', 'Antes', $a, $ba], ['depois', 'Depois', $b, $bb]] as [$name, $label, $s, $r])
      <div class="stack-2">
        <label class="t-overline muted" for="cmp-{{ $name }}">{{ $label }}</label>
        <select id="cmp-{{ $name }}" name="{{ $name }}" class="select" data-autosubmit>
          @foreach ($sessions as $opt)<option value="{{ $opt['date']->toDateString() }}" @selected($opt['date']->equalTo($s['date']))>{{ Fmt::dmy($opt['date']) }}</option>@endforeach
        </select>
        <x-photo-box class="photo-tall {{ $wide ?? false ? 'photo-wide' : '' }}" :src="isset($s['imgs'][$angle]) ? route('files.show', [$s['imgs'][$angle], 'ver' => 1]) : null" :label="$angle.' · '.Fmt::dm($s['date'])" :alt="'Foto '.$angle.' de '.Fmt::dm($s['date'])" />
        @if ($r)<div class="t-caption muted tnum">{{ Fmt::num($r['w']) }} kg · {{ Fmt::num($r['f']) }}% gordura</div>@endif
      </div>
    @endforeach
  </div>
  <noscript><button class="bc-btn bc-btn--secondary bc-btn--block"><x-ic n="rotate" /> Atualizar comparação</button></noscript>
</form>
@if ($ba && $bb)
<div class="bc-card p-3 flex">
  <span class="t-heading"><x-ic n="arrows-left-right" /></span>
  <div class="grow">Entre {{ Fmt::dm($a['date']) }} e {{ Fmt::dm($b['date']) }}</div>
  <div class="right stack-1"><div><x-delta :d="$bb['w'] - $ba['w']" :ind="Indicators::get('w')" /></div><div><x-delta :d="$bb['f'] - $ba['f']" :ind="Indicators::get('f')" /></div></div>
</div>
@endif
