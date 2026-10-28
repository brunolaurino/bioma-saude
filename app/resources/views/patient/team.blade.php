@extends('layouts.simple', ['title' => 'Minha equipe', 'back' => route('profile')])
@section('content')
<div class="stack-2">
  @foreach ($team as $t)
    <div class="bc-card p-3 flex"><x-avatar :name="$t->name" /><div class="grow"><b>{{ $t->name }}</b><div class="t-caption muted"><x-ic :n="$t->icon()" /> {{ $t->specialty }}{{ $t->council_number ? ' · '.$t->council.' '.$t->council_number : '' }}</div></div></div>
  @endforeach
</div>
<p class="t-caption muted">Cada profissional vê só a parte do acompanhamento liberada para ele. Desabafos privados não são compartilhados. <a class="bc-link" href="{{ route('privacy.accesses') }}">Ver quem acessou meus dados</a></p>
@endsection
