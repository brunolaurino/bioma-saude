@extends('layouts.simple', ['title' => 'Notificações', 'back' => route('home')])
@php use App\Support\Fmt; @endphp
@section('content')
@if ($items->isEmpty())
  <x-empty icon="bell-slash" title="Nenhuma notificação" text="Avisos da sua equipe e lembretes aparecem aqui." />
@else
  @if ($items->whereNull('read_at')->isNotEmpty())
    <form method="POST" action="{{ route('notifications.read') }}" class="right">@csrf<button class="bc-btn bc-btn--ghost bc-btn--sm"><x-ic n="check-double" /> Marcar todas como lidas</button></form>
  @endif
  <div class="bc-card divide">
    @foreach ($items as $n)
      @php($tag = $n->link ? 'a' : 'div')
      <{{ $tag }} @if($n->link) href="{{ $n->link }}" @endif class="row-item {{ $n->link ? 'link-row' : '' }}">
        <span class="circle-ic"><x-ic :n="$n->icon" /></span>
        <div class="grow"><div @class(['bold' => ! $n->read_at])>{{ $n->text }}</div><div class="t-caption muted">{{ Fmt::rel($n->created_at) }}</div></div>
        @unless ($n->read_at)<span class="sev-dot bg-primary" aria-label="Não lida"></span>@endunless
      </{{ $tag }}>
    @endforeach
  </div>
@endif
<p class="privacy-line muted"><x-ic n="lock" /> Notificações nunca mostram dados de saúde, para proteger sua privacidade na tela bloqueada.</p>
@endsection
