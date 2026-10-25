@extends('layouts.simple', ['title' => 'Sobre o Bioma CD', 'back' => url()->previous()])
@section('content')
<div class="center stack-3"><span class="brand-mark brand-mark--lg"><x-ic n="leaf" /></span><h2 class="t-title">Bioma CD</h2><p class="muted">Plataforma de Evolução Saudável</p></div>
<p class="muted">Cada consultório tem o próprio ambiente, isolado dos outros, com banco e chaves de criptografia separados. Os dados de saúde ficam cifrados e só a equipe que acompanha o paciente pode ver.</p>
<x-section title="Funções disponíveis neste consultório">
  <div class="bc-card divide">
    @foreach (\App\Security\Modules::ALL as $k => $l)
      <div class="row-item"><span class="grow">{{ $l }}</span>@if ($modules[$k])<x-pill tone="ok" icon="check">Ligado</x-pill> @else<x-pill icon="power-off">Desligado</x-pill> @endif</div>
    @endforeach
  </div>
</x-section>
@endsection
