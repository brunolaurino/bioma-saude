@props(['j', 'href' => null, 'patientName' => null, 'unread' => false, 'lead' => null])
@php use App\Domain\Catalog; use App\Support\Fmt; $m = Catalog::mood((int) $j->mood); $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="bc-card {{ $href ? 'bc-card--action' : '' }} stack-2">
  <div class="flex gap-2 t-caption">
    <span class="t-title c-{{ Catalog::moodTone((int) $j->mood) }}"><x-ic :n="$m['icon']" :r="true" /></span>
    @if ($patientName)<b class="t-body-sm">{{ $patientName }}</b>@endif
    <span class="muted">{{ $patientName ? '· ' : '' }}{{ Fmt::wd($j->day) }}, {{ Fmt::dm($j->day) }} · {{ $m['l'] }}</span>
    @if ($unread)<span class="badge-inline">novo</span>@endif
    <span class="ml-auto muted" title="{{ $j->shared ? 'Compartilhado com '.($lead ?? 'a equipe') : 'Privado' }}"><x-ic :n="$j->shared ? 'eye' : 'lock'" /><span class="sr-only">{{ $j->shared ? 'Compartilhado' : 'Privado' }}</span></span>
  </div>
  <p class="t-body-sm clamp-2">{{ $j->text }}</p>
  @if ($j->tags)<div class="chips">@foreach ((array) $j->tags as $t)<span class="bc-tag">{{ $t }}</span>@endforeach</div>@endif
</{{ $tag }}>
