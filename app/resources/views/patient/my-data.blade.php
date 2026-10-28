@extends('layouts.simple', ['title' => 'Meus dados guardados', 'back' => route('privacy')])
@php use App\Security\Consents; use App\Support\Fmt; @endphp
@section('content')
<x-section title="Cadastro">
  <div class="bc-card divide">
    @foreach ([['user', 'Nome', $patient->name], ['envelope', 'E-mail', $email], ['phone', 'Celular', $patient->phone ?: '-'], ['cake-candles', 'Nascimento', $patient->birth_date ? Fmt::dmy($patient->birth_date) : '-'], ['ruler-vertical', 'Altura', $patient->height ? $patient->height.' m' : '-'], ['bullseye', 'Objetivo', $patient->objective ?: '-']] as [$ic, $l, $v])
      <div class="row-item"><span class="ic-col"><x-ic :n="$ic" /></span><span class="grow muted">{{ $l }}</span><b class="right">{{ $v }}</b></div>
    @endforeach
  </div>
  <a class="bc-link t-body-sm" href="{{ route('profile.data') }}"><x-ic n="pen" /> Corrigir meus dados</a>
</x-section>
<x-section title="Registros de saúde">
  <div class="bc-card divide">
    @foreach ($counts as [$ic, $l, $n])<div class="row-item"><span class="ic-col"><x-ic :n="$ic" /></span><span class="grow">{{ $l }}</span><b class="tnum">{{ $n }}</b></div>@endforeach
  </div>
  <p class="t-caption muted">Tudo fica cifrado com uma chave exclusiva sua. Para ver o conteúdo completo, use Baixar meus dados.</p>
</x-section>
<x-section title="Histórico de consentimentos">
  <div class="bc-card divide">
    @forelse ($history as $c)
      <div class="row-item"><span class="ic-col c-{{ $c->action === 'accept' ? 'ok' : 'warn' }}"><x-ic :n="$c->action === 'accept' ? 'circle-check' : 'circle-xmark'" /></span><div class="grow"><div>{{ Consents::PURPOSES[$c->purpose] ?? $c->purpose }}</div><div class="t-caption muted">{{ $c->action === 'accept' ? 'Aceite' : 'Revogação' }} em {{ Fmt::dmy($c->created_at) }} às {{ Fmt::time($c->created_at) }}{{ $c->notice_version ? ' · aviso v'.$c->notice_version : '' }}</div></div></div>
    @empty
      <p class="p-3 muted">Nenhum registro.</p>
    @endforelse
  </div>
</x-section>
@endsection
