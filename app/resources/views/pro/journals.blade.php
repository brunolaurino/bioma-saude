@extends('layouts.pro', ['title' => 'Desabafos', 'sub' => 'Leia antes das consultas'])
@php use App\Domain\Catalog; use App\Support\Fmt; @endphp
@section('content')
<div class="lg-grid-2 stack-6-mobile">
  <div class="stack-3 {{ $cur ? 'md-hide-when-open' : '' }}">
    <div class="bc-seg" role="group" aria-label="Filtro">
      <a class="bc-seg-link" href="{{ route('pro.journals') }}" @if($filter === 'nao') aria-current="true" @endif>Não lidos ({{ max(0, $unreadCount) }})</a>
      <a class="bc-seg-link" href="{{ route('pro.journals', ['filtro' => 'todos']) }}" @if($filter === 'todos') aria-current="true" @endif>Todos</a>
    </div>
    @forelse ($list as $j)
      <x-journal-card :j="$j" :href="route('pro.journals', array_filter(['abrir' => $j->id, 'filtro' => $filter === 'todos' ? 'todos' : null]))" :patient-name="$names[$j->patient_id] ?? 'Paciente'" :unread="! isset($read[$j->id]) && $j->id !== $cur?->id" />
    @empty
      <x-empty icon="envelope-open" title="Tudo lido" text="Novos desabafos aparecem aqui assim que os pacientes compartilharem." />
    @endforelse
  </div>
  <div>
    @if ($cur)
      @php($m = Catalog::mood((int) $cur->mood))
      <article class="bc-card p-5 stack-4 border-strong sticky-top">
        <a class="bc-link md-hide" href="{{ route('pro.journals', $filter === 'todos' ? ['filtro' => 'todos'] : []) }}"><x-ic n="arrow-left" /> Voltar à lista</a>
        <div class="flex"><x-avatar :name="$names[$cur->patient_id] ?? '?'" /><div class="grow"><b>{{ $names[$cur->patient_id] ?? 'Paciente' }}</b><div class="t-caption muted">{{ Fmt::wdl($cur->day) }}, {{ Fmt::dmy($cur->day) }}</div></div>
          <span class="t-title-lg c-{{ Catalog::moodTone((int) $cur->mood) }}" title="{{ $m['l'] }}"><x-ic :n="$m['icon']" :r="true" /><span class="sr-only">{{ $m['l'] }}</span></span></div>
        <p class="prose">{{ $cur->text }}</p>
        @if ($cur->tags)<div class="chips">@foreach ((array) $cur->tags as $t)<span class="bc-tag">{{ $t }}</span>@endforeach</div>@endif
        <div class="flex wrap gap-2">
          <a class="bc-btn bc-btn--secondary bc-btn--sm" href="{{ route('pro.patient', [$cur->patient_id, 'aba' => 'desabafos']) }}"><x-ic n="folder-open" /> Prontuário</a>
          @if ($canNote)
            <form method="POST" action="{{ route('pro.journals.note', $cur) }}">@csrf<button class="bc-btn bc-btn--secondary bc-btn--sm"><x-ic n="thumbtack" /> Levar para a consulta</button></form>
          @endif
        </div>
      </article>
    @else
      <div class="md-only"><x-empty icon="book-open-reader" title="Escolha um desabafo" text="O texto completo aparece aqui." /></div>
    @endif
  </div>
</div>
@endsection
