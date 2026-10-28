@extends('layouts.patient', ['title' => 'Minha evolução'])
@php use App\Support\Fmt; $tabMeta = ['graf' => ['Gráficos', 'chart-line', route('bio.create')], 'fotos' => ['Fotos', 'images', route('photos.create')], 'exames' => ['Exames', 'file-medical', route('exams.create')]]; @endphp
@section('tab', 'evolution')
@section('header')
<x-header title="Minha evolução">
  <x-slot:right><a class="bc-iconbtn" href="{{ $tabMeta[$tab][2] }}" aria-label="Adicionar"><x-ic n="plus" /></a></x-slot:right>
</x-header>
@endsection
@section('content')
<div class="screen">
  @if (count($tabs) > 1)
  <div class="bc-seg" role="group" aria-label="Seção">
    @foreach ($tabs as $t)
      <a class="bc-seg-link" href="{{ route('evolution', ['aba' => $t]) }}" @if($t === $tab) aria-current="true" @endif><x-ic :n="$tabMeta[$t][1]" /> {{ $tabMeta[$t][0] }}</a>
    @endforeach
  </div>
  @endif

  @if ($tab === 'graf')
    @include('shared.charts', ['charts' => $charts, 'url' => fn ($q) => route('evolution', ['aba' => 'graf'] + $q)])
    @if ($charts['bio']->isNotEmpty())
    <div class="grid-2">
      <a class="bc-btn bc-btn--secondary" href="{{ route('bio.index') }}"><x-ic n="list" /> Histórico</a>
      <a class="bc-btn bc-btn--primary" href="{{ route('bio.create') }}"><x-ic n="plus" /> Registrar</a>
    </div>
    @endif
  @elseif ($tab === 'fotos')
    @if (! $consent)
      <x-empty icon="camera" title="Fotos de evolução" text="As fotos ajudam a ver mudanças que a balança não mostra. Para guardar fotos, precisamos da sua autorização.">
        <a class="bc-btn bc-btn--primary mt-2" href="{{ route('photos.create') }}"><x-ic n="shield-halved" /> Ver autorização</a>
      </x-empty>
    @else
      <div class="grid-2">
        @if ($sessions->count() >= 2)<a class="bc-btn bc-btn--secondary" href="{{ route('photos.compare') }}"><x-ic n="table-columns" /> Comparar</a>@endif
        <a class="bc-btn bc-btn--primary" href="{{ route('photos.create') }}"><x-ic n="camera" /> Novas fotos</a>
      </div>
      @forelse ($sessions as $s)
        <x-section :title="Fmt::dmy($s['date'])">
          <x-slot:action><span class="t-caption muted">{{ Fmt::rel($s['date']) }}</span></x-slot:action>
          <div class="grid-3">
            @foreach (\App\Domain\Catalog::ANGLES as $a)
              <x-photo-box class="photo-tall" :src="isset($s['imgs'][$a]) ? route('files.show', [$s['imgs'][$a], 'ver' => 1]) : null" :label="$a" :alt="'Foto '.$a.' de '.Fmt::dm($s['date'])" />
            @endforeach
          </div>
          <form method="POST" action="{{ route('photos.destroy', $s['date']->toDateString()) }}" data-confirm="Apagar as fotos de {{ Fmt::dm($s['date']) }}?" data-confirm-text="As fotos desta data serão apagadas para sempre." data-confirm-label="Apagar" class="right mt-2">
            @csrf @method('DELETE')<button class="bc-btn bc-btn--ghost bc-btn--sm"><x-ic n="trash-can" /> Apagar fotos desta data</button>
          </form>
        </x-section>
      @empty
        <x-empty icon="images" title="Nenhuma foto ainda" text="Tire as fotos de frente, lado e costas para comparar com as próximas." />
      @endforelse
      <p class="privacy-line muted"><x-ic n="lock" /> Suas fotos ficam cifradas e visíveis só para você e para {{ $lead }}.</p>
    @endif
  @else
    <a class="bc-btn bc-btn--primary bc-btn--block" href="{{ route('exams.create') }}"><x-ic n="file-arrow-up" /> Anexar laudo ou exame</a>
    @if ($exams->isEmpty())
      <x-empty icon="file-medical" title="Nenhum exame anexado" text="Anexe laudos e exames em PDF ou foto para sua equipe ver." />
    @else
      <div class="bc-card divide">
        @foreach ($exams as $e)
          <a data-turbo="false" class="row-item link-row" href="{{ route('files.show', [$e, 'ver' => 1]) }}" target="_blank" rel="noopener">
            <span class="pdf-thumb"><x-ic :n="$e->mime === 'application/pdf' ? 'file-pdf' : 'file-image'" /></span>
            <div class="grow"><div class="bold truncate">{{ $e->name }}</div><div class="t-caption muted">{{ $e->label ?? 'Laudo' }} · {{ Fmt::dm($e->taken_on) }} · {{ Fmt::size($e->size) }}</div></div>
            <x-ic n="eye" class="muted" />
          </a>
        @endforeach
      </div>
    @endif
  @endif
</div>
@endsection
