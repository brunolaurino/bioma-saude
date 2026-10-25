@extends('layouts.simple', ['title' => 'Primeiro acesso', 'back' => route('first.password')])
@section('content')
<p class="t-caption muted">Etapa 3 de 3 · Confirme seus dados</p>
@include('auth._steps', ['step' => 3])
<p class="muted">Esses dados vieram da sua primeira consulta. Confira antes de começar.</p>
<form method="POST" action="{{ route('first.review') }}" class="stack-4" novalidate>
  @csrf
  <x-field name="height" label="Altura" icon="ruler-vertical" :value="$patient->height" suffix="m" inputmode="decimal" placeholder="1,66" />
  @if ($rows)
  <div class="bc-card divide">
    @foreach ($rows as [$ic, $l, $v])
      <div class="row-item"><span class="ic-col"><x-ic :n="$ic" /></span><span class="grow muted">{{ $l }}</span><b>{{ $v }}</b></div>
    @endforeach
  </div>
  @endif
  <p class="privacy-line muted"><x-ic n="circle-info" /> Metas definidas por {{ $lead }}. Elas podem ser ajustadas a qualquer momento.</p>
  <button class="bc-btn bc-btn--primary bc-btn--block"><x-ic n="circle-check" /> Começar a usar</button>
</form>
@endsection
