@extends('layouts.base')
@section('body')
<main id="conteudo" class="simple center stack-4">
  <span class="brand-mark brand-mark--lg"><x-ic :n="$icon" /></span>
  <h1 class="t-title-lg">{{ $heading }}</h1>
  <p class="muted">{{ $text }}</p>
  <div class="flex justify-center wrap">
    @auth
      <a class="bc-btn bc-btn--primary" href="{{ auth()->user()->isProfessional() ? route('pro.dashboard') : route('home') }}"><x-ic n="house" /> Voltar ao início</a>
    @else
      <a class="bc-btn bc-btn--primary" href="{{ route('login') }}"><x-ic n="right-to-bracket" /> Entrar</a>
    @endauth
  </div>
</main>
@endsection
