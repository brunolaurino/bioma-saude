@extends('layouts.simple', ['title' => 'Confirme sua senha', 'back' => $back])
@section('content')
<x-empty icon="lock" title="Confirme que é você" text="Para esta ação, digite sua senha de novo. A confirmação vale por 10 minutos." />
<form method="POST" action="{{ route('reauth') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="password" label="Senha" icon="key" type="password" autocomplete="current-password" required />
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="check" /> Confirmar</button>
</form>
@endsection
