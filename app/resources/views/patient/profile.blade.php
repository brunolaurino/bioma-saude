@extends('layouts.simple', ['title' => 'Perfil', 'back' => route('home')])
@php use App\Support\Fmt; @endphp
@section('content')
<div class="flex">
  <x-avatar :name="$patient->name" size="lg" />
  <div><div class="t-heading">{{ $patient->name }}</div><div class="t-caption muted">Paciente desde {{ Fmt::monthYear($patient->started_on) }}{{ $patient->age() ? ' · '.$patient->age().' anos' : '' }}</div></div>
</div>
@if ($stats)
<div class="grid-3 center">
  @foreach ($stats as [$l, $v])<div class="bc-card p-2"><div class="bold tnum">{{ $v }}</div><div class="t-overline muted">{{ $l }}</div></div>@endforeach
</div>
@endif
@if ($achievements)
<x-section title="Conquistas">
  <div class="grid-3">
    @foreach ($achievements as [$ic, $l, $on, $p])
      <div @class(['badge-card', 'on' => $on])>
        <span class="badge-card__ic"><x-ic :n="$on ? $ic : 'lock'" /></span>
        <span class="t-caption bold">{{ $l }}</span>
        @if (! $on && $p)<span class="t-caption tnum">{{ $p }}</span>@endif
      </div>
    @endforeach
  </div>
</x-section>
@endif
<div class="bc-card bc-list divide">
  <x-list-link :href="route('profile.data')" icon="user" label="Meus dados" />
  @if ($m['metas'])<x-list-link :href="route('profile.goals')" icon="bullseye" label="Minhas metas" hint="Peso, água e proteína" />@endif
  @if ($m['lembretes'])<x-list-link :href="route('profile.reminders')" icon="bell" label="Lembretes" hint="Água, metas, terapia" />@endif
  @if ($m['equipe'])<x-list-link :href="route('profile.team')" icon="users" label="Minha equipe" />@endif
  <x-list-link :href="route('privacy')" icon="shield-halved" label="Privacidade e dados" hint="Consentimentos, exportar, pedidos" />
  <x-list-link :href="route('profile.sessions')" icon="laptop-mobile" label="Aparelhos conectados" />
  <x-list-link :href="route('profile.password')" icon="key" label="Trocar senha" />
  <x-list-link :href="route('help')" icon="circle-question" label="Ajuda" />
</div>
<form method="POST" action="{{ route('logout') }}" class="bc-card" data-confirm="Sair da conta?" data-confirm-text="Você vai precisar do e-mail e da senha para entrar de novo." data-confirm-label="Sair">
  @csrf
  <button class="bc-listrow bc-listrow--danger w-full"><span class="bc-listrow__icon"><x-ic n="right-from-bracket" /></span><span class="bc-listrow__text">Sair</span></button>
</form>
<p class="t-caption muted center">Saúde App · versão 1.0 · <a class="bc-link" href="{{ route('about') }}">Sobre</a></p>
@endsection
