@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
<div class="stack-4 max-lg">
  <x-section title="Humor nas últimas 4 semanas"><div class="max-sm"><x-mood-calendar :journal="$journal" /></div></x-section>
  <p class="privacy-line"><x-ic n="lock" /> Aparecem aqui só os desabafos que {{ $patient->firstName() }} escolheu compartilhar.</p>
  @forelse ($journal as $j)
    @php($m = Catalog::mood((int) $j->mood))
    <article class="bc-card p-4 stack-2">
      <div class="flex gap-2"><span class="t-title c-{{ Catalog::moodTone((int) $j->mood) }}"><x-ic :n="$m['icon']" :r="true" /></span><b>{{ $m['l'] }}</b><span class="t-caption muted">· {{ Fmt::wdl($j->day) }}, {{ Fmt::dmy($j->day) }}</span></div>
      <p class="prose t-body-sm">{{ $j->text }}</p>
      @if ($j->tags)<div class="chips">@foreach ((array) $j->tags as $t)<span class="bc-tag">{{ $t }}</span>@endforeach</div>@endif
      @if ($canWrite('notas'))
        <form method="POST" action="{{ route('pro.journals.note', $j) }}" class="right">@csrf<button class="bc-btn bc-btn--ghost bc-btn--sm"><x-ic n="thumbtack" /> Levar para a consulta</button></form>
      @endif
    </article>
  @empty
    <x-empty icon="book" title="Nenhum desabafo compartilhado" />
  @endforelse
</div>
