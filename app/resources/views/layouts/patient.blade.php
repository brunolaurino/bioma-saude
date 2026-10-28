@extends('layouts.base')
@section('body')
@php($hasTabs = View::hasSection('tab'))
<div @class(['app-patient', 'page-'.trim(View::yieldContent('page')) => View::hasSection('page')])>
  @yield('header')
  <main id="conteudo" @class(['no-tabs' => ! $hasTabs])>
    @yield('content')
  </main>
  @if ($hasTabs)
    @php($tab = trim(View::yieldContent('tab')))
    <div class="tabbar-wrap">
      <nav class="bc-tabbar" aria-label="Navegação principal">
        <a class="bc-tab" href="{{ route('home') }}" @if($tab === 'home') aria-current="page" @endif>
          <x-ic n="house" /> Início
          @if ($nav['unread'] > 0)<span class="bc-badge">{{ $nav['unread'] }}<span class="sr-only"> notificações</span></span>@endif
        </a>
        @if ($nav['m']['metas'] || $nav['m']['sintomas'])
        <a class="bc-tab" href="{{ route('habits') }}" @if($tab === 'habits') aria-current="page" @endif>
          <x-ic n="list-check" /> Metas
          @if ($nav['remaining'] > 0)<span class="bc-badge">{{ $nav['remaining'] }}<span class="sr-only"> metas faltando</span></span>@endif
        </a>
        @else <span></span> @endif
        <div class="fab-cell">
          <a class="bc-fab" href="{{ route('quick') }}" data-open="sheet-quick" aria-label="Registrar"><x-ic n="plus" /></a>
        </div>
        @if ($nav['m']['bioimpedancia'] || $nav['m']['fotos'] || $nav['m']['exames'])
        <a class="bc-tab" href="{{ route('evolution') }}" @if($tab === 'evolution') aria-current="page" @endif><x-ic n="chart-line" /> Evolução</a>
        @else <span></span> @endif
        @if ($nav['m']['nutricao'] || $nav['m']['atividade'] || $nav['m']['saude_mental'])
        <a class="bc-tab" href="{{ route('care') }}" @if($tab === 'care') aria-current="page" @endif><x-ic n="heart-pulse" /> Cuidado</a>
        @else <span></span> @endif
      </nav>
    </div>
    <dialog class="sheet" id="sheet-quick" aria-labelledby="quick-title">
      <div class="bc-sheet">
        <div class="bc-sheet__head">
          <h2 class="bc-sheet__title grow" id="quick-title">O que você quer registrar?</h2>
          <button type="button" class="bc-iconbtn sheet-close" data-close aria-label="Fechar"><x-ic n="xmark" /></button>
        </div>
        @include('patient.partials.quick-grid')
      </div>
    </dialog>
  @endif
</div>
@include('patient.partials.install-sheet')
@endsection
