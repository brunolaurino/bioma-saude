@extends('layouts.simple', ['title' => 'Primeiro acesso', 'back' => route('login')])
@section('content')
<p class="t-caption muted">Etapa 1 de 3 · Código do convite</p>
@include('auth._steps', ['step' => 1])
<p class="muted">{{ $lead }} enviou um código de convite por e-mail ou WhatsApp. Digite abaixo.</p>
<form method="POST" action="{{ route('first') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="code" label="Código do convite" icon="ticket" placeholder="ABCD-2345" hint="São 8 letras e números. O código vale por 7 dias." autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required />
  <button class="bc-btn bc-btn--primary bc-btn--block">Continuar <x-ic n="arrow-right" /></button>
</form>
@endsection
