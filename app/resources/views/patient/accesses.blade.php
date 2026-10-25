@extends('layouts.simple', ['title' => 'Quem acessou meus dados', 'back' => route('privacy')])
@php use App\Support\{AuditLabels, Fmt}; @endphp
@section('content')
<p class="muted">Cada vez que alguém da equipe abre ou muda seus dados, fica um registro que não pode ser apagado.</p>
@if ($rows->isEmpty())
  <x-empty icon="eye" title="Nenhum acesso registrado" />
@else
<div class="bc-card divide">
  @foreach ($rows as $r)
    <div class="row-item"><div class="grow"><div class="bold">{{ preg_replace('/\s*\((master|equipe)\)$/', '', $r->actor) }}</div><div class="t-caption muted">{{ AuditLabels::label($r->action) }}</div></div><span class="t-caption muted right tnum">{{ Fmt::dm($r->occurred_at) }}<br>{{ Fmt::time($r->occurred_at) }}</span></div>
  @endforeach
</div>
<p class="t-caption muted">Mostrando os 100 registros mais recentes. Para o histórico completo, faça um pedido de informação.</p>
@endif
@endsection
