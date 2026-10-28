@extends('layouts.base', ['title' => 'Histórico de bioimpedância'])
@php use App\Domain\Indicators; use App\Support\Fmt; @endphp
@section('body')
<div class="app-patient">
  <x-header title="Histórico de bioimpedância" :sub="$bio->count().' '.Fmt::plural($bio->count(), 'registro', 'registros')" :back="route('evolution')">
    <x-slot:right><a class="bc-iconbtn" href="{{ route('bio.create') }}" aria-label="Novo registro"><x-ic n="plus" /></a></x-slot:right>
  </x-header>
  <main id="conteudo" class="no-tabs">
    @if ($bio->isEmpty())
      <div class="screen"><x-empty icon="weight-scale" title="Nenhum registro" text="Registre sua primeira bioimpedância." /></div>
    @else
    <ul class="plain divide list-surface">
      @foreach ($bio as $i => $b)
        @php($prev = $bio[$i + 1] ?? null)
        <li><a class="row-item link-row p-4" href="{{ route('bio.show', $b['id']) }}">
          <span class="date-box"><b class="tnum">{{ $b['d']->day }}</b><span>{{ Fmt::MON[$b['d']->month - 1] }}</span></span>
          <div class="grow">
            <div class="bold tnum">{{ Fmt::num($b['w']) }} kg <span class="normal muted">· {{ Fmt::num($b['f']) }}% gordura</span></div>
            <div class="t-caption muted flex gap-2">@if ($prev)<x-delta :d="$b['w'] - $prev['w']" :ind="Indicators::get('w')" />@else Registro inicial @endif @if ($b['file_id'])<span><x-ic n="paperclip" /> laudo</span>@endif</div>
          </div>
          <x-ic n="chevron-right" class="muted" />
        </a></li>
      @endforeach
    </ul>
    @endif
  </main>
</div>
@endsection
