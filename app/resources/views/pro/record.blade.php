@php
  use App\Support\{Clock, Fmt};
  $p = $patient;
  $sub = collect([$p->age() ? $p->age().' anos' : null, 'desde '.Fmt::monthYear($p->started_on), $p->objective])->filter()->join(' · ');
@endphp
@extends('layouts.pro', ['title' => $p->name, 'sub' => $sub, 'back' => route('pro.patients')])
@section('actions')
<a class="bc-btn bc-btn--primary bc-btn--sm" href="{{ route('pro.patient.prep', $p) }}"><x-ic n="clipboard-list" /> <span class="sm-hide">Preparar consulta</span></a>
@endsection
@section('subnav')
<nav class="ptabs" aria-label="Seções do prontuário">
  @foreach ($tabs as $k => [$label, $icon])
    <a href="{{ route('pro.patient', [$p, 'aba' => $k]) }}" @if($tab === $k) aria-current="page" @endif><x-ic :n="$icon" /> {{ $label }}
      @if ($k === 'sintomas' && $row['alerts'])<span class="bc-badge bc-badge--bad">{{ $row['alerts'] }}</span>@endif
      @if ($k === 'desabafos' && $row['unread'] && $tab !== 'desabafos')<span class="bc-badge">{{ $row['unread'] }}</span>@endif
    </a>
  @endforeach
</nav>
@endsection
@section('content')
@if (session('invite_code'))
  <div class="bc-card p-4 stack-3 border-strong max-md">
    <div class="flex"><span class="circle-ic circle-ic--lg"><x-ic n="paper-plane" /></span><div><b>Convite enviado para {{ $p->firstName() }}</b><div class="t-caption muted">{{ $p->user?->email }}</div></div></div>
    <div class="center stack-1">
      <div class="t-overline muted">Código de primeiro acesso</div>
      <div class="code-big" id="invite-code">{{ session('invite_code') }}</div>
      <button type="button" class="bc-btn bc-btn--secondary bc-btn--sm" data-copy="{{ session('invite_code') }}"><x-ic n="copy" /> Copiar código</button>
    </div>
    <p class="t-caption muted">Você também pode mandar o código por WhatsApp. Ele vale por 7 dias e aparece só agora: se perder, gere outro.</p>
  </div>
@endif
@include('pro.record._'.$tab)
@endsection
