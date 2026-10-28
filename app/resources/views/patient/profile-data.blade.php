@extends('layouts.simple', ['title' => 'Meus dados', 'back' => route('profile')])
@php use App\Support\Fmt; @endphp
@section('content')
<form method="POST" action="{{ route('profile.data') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="name" label="Nome completo" icon="user" :value="$patient->name" autocomplete="name" required />
  <div class="bc-field">
    <span class="bc-field__label">E-mail</span>
    <div class="bc-field__box is-readonly"><span class="bc-field__icon"><x-ic n="envelope" /></span><span class="grow truncate">{{ $email }}</span><x-ic n="lock" class="muted" /></div>
    <span class="bc-field__hint">Para trocar o e-mail de acesso, fale com o consultório.</span>
  </div>
  <x-field name="phone" label="Celular" icon="phone" :value="$patient->phone" inputmode="tel" autocomplete="tel" placeholder="(11) 90000-0000" />
  <div class="grid-2 gap-3">
    <x-field name="birth_date" label="Nascimento" icon="cake-candles" :value="$patient->birth_date ? Fmt::dmy($patient->birth_date) : null" inputmode="numeric" placeholder="dd/mm/aaaa" />
    <x-field name="height" label="Altura" icon="ruler-vertical" :value="$patient->height" suffix="m" inputmode="decimal" placeholder="1,66" />
  </div>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="floppy-disk" /> Salvar</button>
</form>
@endsection
