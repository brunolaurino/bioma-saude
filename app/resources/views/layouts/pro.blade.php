@extends('layouts.base')
@section('body')
@php
  $r = request()->route()?->getName() ?? '';
  $items = array_filter([
    ['pro.dashboard', 'gauge', 'Painel', null, false],
    ['pro.patients', 'users', 'Pacientes', null, false],
    ['pro.alerts', 'triangle-exclamation', 'Alertas', $proNav['alerts'], true],
    ['pro.journals', 'book-open', 'Desabafos', $proNav['journals'], false],
    $proNav['agenda'] ? ['pro.agenda', 'calendar-days', 'Agenda', null, false] : null,
    ['pro.settings', 'gear', 'Configurações', null, false],
  ]);
  $active = fn ($name) => $r === $name || ($name === 'pro.patients' && str_starts_with($r, 'pro.patient')) || ($name === 'pro.settings' && in_array($r, ['pro.audit', 'pro.requests', 'pro.incidents', 'pro.account'], true));
@endphp
<div class="app-pro">
  <aside class="side on-dark" aria-label="Menu do painel">
    <div class="side__brand"><x-brand :size="44" /><div class="grow"><b>Saúde App</b><div class="t-overline muted">Painel profissional</div></div><x-theme-toggle /></div>
    <nav>
      @foreach ($items as [$name, $ic, $label, $count, $bad])
        <a class="side__link" href="{{ route($name) }}" @if($active($name)) aria-current="page" @endif><span class="ic-col"><x-ic :n="$ic" /></span><span class="grow">{{ $label }}</span>@if ($count)<span class="count {{ $bad ? 'count--bad' : '' }}">{{ $count }}</span>@endif</a>
      @endforeach
      @if ($proNav['master'])
        <div class="t-overline muted side__group">LGPD e segurança</div>
        <a class="side__link" href="{{ route('pro.requests') }}" @if($r === 'pro.requests') aria-current="page" @endif><span class="ic-col"><x-ic n="envelope-open-text" /></span><span class="grow">Pedidos de titulares</span>@if ($proNav['requests'])<span class="count count--bad">{{ $proNav['requests'] }}</span>@endif</a>
        <a class="side__link" href="{{ route('pro.audit') }}" @if($r === 'pro.audit') aria-current="page" @endif><span class="ic-col"><x-ic n="clipboard-list" /></span><span class="grow">Auditoria</span></a>
        <a class="side__link" href="{{ route('pro.incidents') }}" @if($r === 'pro.incidents') aria-current="page" @endif><span class="ic-col"><x-ic n="shield-virus" /></span><span class="grow">Incidentes</span></a>
      @endif
    </nav>
    <div class="side__foot stack-2">
      <a class="bc-btn bc-btn--primary bc-btn--block bc-btn--sm" href="{{ route('pro.patients.create') }}"><x-ic n="user-plus" /> Novo paciente</a>
      <a class="flex p-2 link-plain" href="{{ route('pro.account') }}"><x-avatar :name="$proNav['me']->name" size="sm" /><span class="t-caption"><b class="block">{{ $proNav['me']->name }}</b><span class="muted">{{ $proNav['me']->specialty }}{{ $proNav['master'] ? ' · master' : '' }}</span></span></a>
    </div>
  </aside>
  <main id="conteudo">
    @unless (View::hasSection('banner'))
    <x-header :title="$title ?? ''" :sub="$sub ?? null" :back="$back ?? null">
      <x-slot:right>
        <div class="flex gap-2">
          @yield('actions')
          <x-theme-toggle class="md-hide" />
          <a class="bc-iconbtn md-hide" href="{{ route('pro.settings') }}" aria-label="Configurações"><x-ic n="gear" /></a>
        </div>
      </x-slot:right>
    </x-header>
    @endunless
    @yield('subnav')
    <div class="pro-content">
      @yield('banner')
      @yield('content')
    </div>
  </main>
  <div class="tabbar-wrap">
    <nav class="bc-tabbar bc-tabbar--5" aria-label="Navegação">
      @foreach (array_slice($items, 0, 5) as [$name, $ic, $label, $count, $bad])
        <a class="bc-tab" href="{{ route($name) }}" @if($active($name)) aria-current="page" @endif><x-ic :n="$ic" /> {{ $label }}@if ($count)<span class="bc-badge {{ $bad ? 'bc-badge--bad' : '' }}">{{ $count }}</span>@endif</a>
      @endforeach
    </nav>
  </div>
</div>
@endsection
