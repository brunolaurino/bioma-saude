@extends('layouts.entry', ['title' => 'Códigos de recuperação'])
@section('content')
<div class="stack-4">
  <span class="circle-ic circle-ic--done"><x-ic n="check" /></span>
  <h1 class="t-title">Segundo fator ativado</h1>
  <p class="muted">Guarde estes códigos num lugar seguro. Cada um entra uma vez se você perder o celular. Eles não serão mostrados de novo.</p>
  <div class="bc-card p-4 codes">@foreach ($codes as $c)<span>{{ $c }}</span>@endforeach</div>
  <button type="button" class="bc-btn bc-btn--secondary bc-btn--block" data-copy="{{ implode("\n", $codes) }}"><x-ic n="copy" /> Copiar códigos</button>
  <a class="bc-btn bc-btn--primary bc-btn--block" href="{{ route('pro.dashboard') }}">Guardei os códigos <x-ic n="arrow-right" /></a>
</div>
@endsection
