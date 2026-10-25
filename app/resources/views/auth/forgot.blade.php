@extends('layouts.simple', ['title' => 'Recuperar senha', 'back' => route('login')])
@section('content')
@if (session('sent'))
  <x-empty icon="envelope-open-text" title="Verifique seu e-mail" text="Se houver uma conta com {{ session('sent') }}, enviamos um link para criar uma nova senha. Ele vale por 30 minutos.">
    <a class="bc-btn bc-btn--secondary mt-2" href="{{ route('login') }}"><x-ic n="arrow-left" /> Voltar ao login</a>
  </x-empty>
@else
  <p class="muted">Digite o e-mail cadastrado. Você vai receber um link para criar uma nova senha.</p>
  <form method="POST" action="{{ route('password.request') }}" class="stack-4" novalidate>
    @csrf
    <x-field name="email" label="E-mail" icon="envelope" type="email" autocomplete="username" inputmode="email" placeholder="seu@email.com" required />
    <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="paper-plane" /> Enviar link</button>
  </form>
@endif
@endsection
